<?php

namespace Tests\Unit;

use App\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
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

    /**
     * @var null
     */
    protected $auth = null;

    /**
     *
     */
    public function testUserModelInstance()
    {
        $user = TokenApp::makeUserModel();
        $this->assertInstanceOf(TokenApp::getUserClass(), $user);
    }

    /**
     *
     */
    public function testGuardInstance()
    {
        $this->assertInstanceOf(TokensGuard::class, TokenApp::guard());
    }

    /**
     *
     */
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

    /**
     *
     */
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
     * @throws \Exception
     */
    public function testLoginRoute()
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
            'user' => [$usernameField],
            'auth' => [
                'token',
            ],
        ]);

        $this->assertDatabaseHas(TokenApp::config('table_tokens'), [
            'token' => $responseData['auth']['token'],
        ]);
    }

    /**
     *
     */
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
            'auth' => [
                'token',
            ],
        ]);

        $this->assertDatabaseHas(TokenApp::makeUserModel()->getTable(), array_only($data, [
            TokenApp::config('username'),
        ]));

//        $this->json()
    }

    /**
     * @throws \Exception
     */
    public function testUserRoute()
    {
        $this->expectsEvents([ApiAuthenticating::class, ApiAuthenticated::class]);

        $response = $this->json('GET', TokenApp::routeUri('user'), [], $this->auth()->headers);

        $response->assertStatus(200);

        $responseData = json_decode($response->getContent(), true);
        $usernameField = TokenApp::config('username');
        $response->assertJsonStructure([$usernameField]);
        $this->assertEquals($responseData[$usernameField], $this->auth()->user->{$usernameField});
    }

    /**
     * @throws \Exception
     */
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


    /**
     * @throws \Exception
     */
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
     * @throws \Exception
     */
    public function testDevices()
    {
        $user = $this->auth()->user;
        // Assign 9 more tokens to user.
        for ($i = 0; $i < 9; $i++) {
            $user->issueToken();
        }

        $response = $this->json('GET', TokenApp::routeUri('devices'), [], $this->auth()->headers);
        $response->assertStatus(200);
        $response->assertJsonStructure([
            '*' => [
                'id', 'user_agent', 'device', 'updated_at', 'created_at'
            ]
        ]);

        $this->assertEquals(count(json_decode($response->getContent())), $user->apiTokens()->count());
    }

    /**
     * @throws \Exception
     */
    public function testDeviceLogout()
    {
        $user = $this->auth()->user;
        // Assign 9 more tokens to user.
        for ($i = 0; $i < 9; $i++) {
            $user->issueToken();
        }


        $token = $user->apiTokens->random();
        $response = $this->json(
            'POST',
            TokenApp::routeUri('devices') . "/logout/{$token->id}",
            [],
            $this->auth()->headers
        );
        $response->assertStatus(200);
        $response->assertJson(['success' => 1]);
        $this->assertSoftDeleted(TokenApp::config('table_tokens'), [
            'id' => $token->id
        ]);
    }


    /**
     *
     */
    public function testPasswordEmail()
    {

        $user = $this->auth()->user;

        $usernameField = TokenApp::config('username');


        $requestData = [
            'client_id' => ApiClient::make(str_random(16))->value,
            'email' => $user->email,
        ];

        Notification::fake();

        $response = $this->json('POST', TokenApp::routeUri('password_email'), $requestData);

        Notification::assertSentTo(
            [$user], ResetPassword::class
        );

        $this->assertDatabaseHas('password_resets', [
            'email' => $user->email,
        ]);

        $response->assertSuccessful();

        $responseData = json_decode($response->getContent(), true);
        $response->assertJson([
            'message' => 'We have e-mailed your password reset link!'
        ]);
    }

    /**
     *
     */
    public function testArtisanMakeClient()
    {
        $name = str_random();
        Artisan::call('landman:tokens:make-client', ['name' => $name]);
        $this->assertDatabaseHas(TokenApp::config('table_clients'), [
            'name' => $name
        ]);
    }

    /**
     *
     */
    public function testArtisanDeleteClient()
    {
        $name = str_random();
        $client = ApiClient::make($name);
        $this->assertDatabaseHas(TokenApp::config('table_clients'), [
            'name' => $name
        ]);

        Artisan::call('landman:tokens:delete-client', ['name' => $name, '--yes' => true]);

        $this->assertDatabaseMissing(TokenApp::config('table_clients'), [
            'name' => $name
        ]);
    }


    /**
     *
     */
    public function testArtisanRefreshClient()
    {
        $name = str_random();
        $client = ApiClient::make($name);
        $old = $client->value;

        $this->assertNotEmpty($old);
        $this->assertDatabaseHas(TokenApp::config('table_clients'), [
            'name' => $name,
            'value' => $old,
        ]);

        Artisan::call('landman:tokens:refresh-client', ['name' => $name, '--yes' => true]);

        $new = $client->fresh()->value;

        $this->assertNotEquals($old, $new);
        $this->assertDatabaseHas(TokenApp::config('table_clients'), [
            'name' => $name,
            'value' => $new,
        ]);
    }

    /**
     *
     */
    public function testArtisanListClients()
    {
        $name = str_random();
        Artisan::call('landman:tokens:list-clients');
        $this->assertNotEmpty(Artisan::output());
    }

    /**
     * @return \stdClass
     */
    protected function auth()
    {
        if ($this->auth)
            return $this->auth;

        $user = factory(TokenApp::getUserClass())->create([
            'password' => bcrypt('secret'),
        ]);
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
