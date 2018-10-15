<?php

namespace Tests\Unit;

use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Landman\MultiTokenAuth\Classes\TokenApp;
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
    public function testLoginAndUserRoutes()
    {
        $passwordField = TokenApp::config('password_field');
        $usernameField = TokenApp::config('username');
        $user = factory(TokenApp::getUserClass())->create([
            $passwordField => bcrypt('secret'),
        ]);

        $client = ApiClient::make(str_random(16));

        $requestData = [
            'client_id' => $client->value,
            $usernameField => $user->{$usernameField},
            $passwordField => 'secret',
        ];

        $response = $this->json('POST', TokenApp::routeUri('login'), $requestData);

        $response->assertSuccessful();

        $responseData = json_decode($response->getContent(), true);
        $response->assertJsonStructure([
            'user' => [$usernameField], 'token',
        ]);

        $this->assertDatabaseHas(TokenApp::config('table_tokens'), [
            'token' => $responseData['token'],
        ]);

        $response = $this->json('GET', TokenApp::routeUri('user'), $requestData);

        $response->assertSuccessful();

        $responseData = json_decode($response->getContent(), true);
        $response->assertJsonStructure([$usernameField]);
        $this->assertEquals($responseData[$usernameField], $user->{$usernameField});
    }

}
