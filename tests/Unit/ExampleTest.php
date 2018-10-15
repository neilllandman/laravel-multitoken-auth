<?php

namespace Tests\Unit;

use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Landman\MultiTokenAuth\Auth\TokensGuard;
use Landman\MultiTokenAuth\Classes\TokenApp;
use Landman\MultiTokenAuth\Console\Commands\MakeApiClient;
use Landman\MultiTokenAuth\Events\ApiAuthenticated;
use Landman\MultiTokenAuth\Events\ApiAuthenticating;
use Landman\MultiTokenAuth\Events\ApiLogin;
use Landman\MultiTokenAuth\Events\ApiLogout;
use Landman\MultiTokenAuth\Events\ApiRegistered;
use Landman\MultiTokenAuth\Models\ApiClient;
use Landman\MultiTokenAuth\Models\ApiToken;
use Tests\TestCase;

/**
 * Class ExampleTest
 * @package Tests\Unit
 */
class ExampleTest extends TestCase
{
    use DatabaseTransactions;

    protected $auth = null;

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testBasicTest()
    {
        $this->assertTrue(true);
    }

    /**
     *
     */
    public function testUserModelInstance()
    {
        $user = TokenApp::makeUserModel();
        $this->assertInstanceOf(TokenApp::getUserClass(), $user);
    }

    public function testGuardInstance()
    {
        $this->assertInstanceOf(TokensGuard::class, TokenApp::guard());
    }

    public function testMakeClient()
    {
        $name = str_random(16);
        $client = ApiClient::make($name);
        $this->assertDatabaseHas(TokenApp::config('table_clients'), [
            'name' => $name,
            'id' => $client->id,
            'value' => $client->value,
        ]);
    }

    public function testValidateClientId()
    {
        $this->assertFalse(TokenApp::validateClientId(str_random()));
        $client = ApiClient::make(str_random());

        $this->assertTrue(TokenApp::validateClientId($client->value));
    }

    /**
     *
     */
    public function testAddToken()
    {
        $user = factory(TokenApp::getUserClass())->create();

        $token = $user->issueToken();
        $this->assertDatabaseHas(TokenApp::config('table_tokens'), ['id' => $token->id]);
    }

    /**
     *
     */
    public function testLogiRoutes()
    {
        $passwordField = TokenApp::config('password_field');
        $usernameField = TokenApp::config('username');
        $user = factory(TokenApp::getUserClass())->create([
            $passwordField => bcrypt('secret'),
        ]);


        $requestData = [
            'client_id' => ApiClient::make(str_random(16))->value,
            $usernameField => $user->{$usernameField},
            $passwordField => 'secret',
        ];

        $this->expectsEvents([ApiLogin::class]);
        $response = $this->json('POST', TokenApp::routeUri('login'), $requestData);

        $response->assertSuccessful();

        $responseData = json_decode($response->getContent(), true);
        $response->assertJsonStructure([
            'user' => [$usernameField], 'token',
        ]);

        $this->assertDatabaseHas(TokenApp::config('table_tokens'), [
            'token' => $responseData['token'],
        ]);
    }

    public function testRegisterRoute()
    {
        $client = ApiClient::make(str_random());
        $this->assertDatabaseHas(TokenApp::config('table_clients'), [
            'id' => $client->id,
        ]);
        $user = factory(TokenApp::getUserClass())->make([
            'password' => 'secret_password',
            'password_confirmation' => 'secret_password',
            'device' => 'SecretTestDevice',
            'client_id' => $client->value,
        ]);
        $user->makeVisible(['password']);
        $data = $user->toArray();

        $response = $this->json('POST', TokenApp::routeUri('register'), $data);
        $response->assertOk();

        $response->assertJsonStructure([
            'user' => [
                TokenApp::config('username'),
            ],
            'token',
        ]);

        $this->assertDatabaseHas(TokenApp::makeUserModel()->getTable(), array_only($data, [
            TokenApp::config('username'),
        ]));

//        $this->json()
    }

    public function testUserRoute()
    {
        // Assert via Authorization header
        $this->expectsEvents([ApiAuthenticating::class, ApiAuthenticated::class]);

        $response = $this->json('GET', TokenApp::routeUri('user'), [], $this->auth()->headers);

        $response->assertStatus(200);

        $responseData = json_decode($response->getContent(), true);
        $usernameField = TokenApp::config('username');
        $response->assertJsonStructure([$usernameField]);
        $this->assertEquals($responseData[$usernameField], $this->auth()->user->{$usernameField});
    }

    public function testLogout()
    {
        $user = $this->auth()->user;

        $this->expectsEvents([ApiLogout::class]);
        $response = $this->json('POST', TokenApp::routeUri('logout'), [], $this->auth()->headers);
        $response->assertStatus(200);
        $token = $this->auth()->token;

        $this->assertSoftDeleted(TokenApp::config('table_tokens'), [
            'id' => $token->id, 'token' => $token->token,
        ]);
        $response->assertJson(['success' => 1]);
    }


    public function testLogoutAll()
    {
        $user = $this->auth()->user;
        // Assign 9 more tokens to user.
        for ($i = 0; $i < 9; $i++) {
            $user->issueToken();
        }

        $this->assertGreaterThan(1, $user->apiTokens()->count());

        $this->expectsEvents([ApiLogout::class]);
        $response = $this->json('POST', TokenApp::routeUri('logout_all'), [], $this->auth()->headers);
        $response->assertStatus(200);
        $response->assertJson(['success' => 1]);

        $this->assertEquals(0, $user->apiTokens()->count());
    }

    /**
     * @return \stdClass
     */
    protected function auth()
    {
        if ($this->auth)
            return $this->auth;

        $user = factory(TokenApp::getUserClass())->create();
        $token = $user->issueToken();
        $this->assertDatabaseHas(TokenApp::config('table_tokens'), [
            'id' => $token->id, 'token' => $token->token
        ]);
        $auth = [
            'user' => $user,
            'token' => $token,
            'headers' => [
                'Authorization' => "Bearer {$token->token}"
            ]
        ];
        $this->auth = (object)$auth;
        return $this->auth;
    }
}
