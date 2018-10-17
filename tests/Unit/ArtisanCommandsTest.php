<?php

namespace Landman\MultiTokenAuth\Tests\Unit;

use App\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
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
class ArtisanCommandsTest extends TestCase
{
    use DatabaseTransactions;

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
        $client = ApiClient::where('name', $name)->first();
        $this->assertTrue(str_contains(Artisan::output(), $client->value));

        // Fail
        Artisan::call('landman:tokens:make-client', ['name' => $name]);
        $this->assertStringStartsWith(
            "Client with name {$name} already exists!",
            Artisan::output()
        );
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

        $this->assertStringStartsWith(
            "Client {$client->name} deleted!",
            Artisan::output()
        );

        $this->assertDatabaseMissing(TokenApp::config('table_clients'), [
            'name' => $name
        ]);

        // Fail
        $invalidName = 'someinvalidname';
        Artisan::call('landman:tokens:delete-client', ['name' => $invalidName, '--yes' => true]);
        $this->assertStringStartsWith(
            "No client with name {$invalidName} found!",
            trim(Artisan::output())
        );
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
        $this->assertStringStartsWith(
            "Client updated!",
            trim(Artisan::output())
        );
        $new = $client->fresh()->value;
        $this->assertNotEquals($old, $new);
        $this->assertDatabaseHas(TokenApp::config('table_clients'), [
            'name' => $name,
            'value' => $new,
        ]);

        // fail
        $invalidName = 'someinvalidname';
        Artisan::call('landman:tokens:refresh-client', ['name' => $invalidName, '--yes' => true]);
        $this->assertStringStartsWith(
            "No client with name {$invalidName} found!",
            trim(Artisan::output())
        );
    }

    /**
     *
     */
    public function testArtisanListClients()
    {
        for ($i = 0; $i < 3; $i++) {
            ApiClient::make(str_random());
        }
        $name = str_random();
        Artisan::call('landman:tokens:list-clients');
        $output = Artisan::output();
        $this->assertNotEmpty($output);
        foreach (ApiClient::all() as $client) {
            $this->assertTrue(preg_match("/{$client->name}.+{$client->value}/", $output) > 0);
        }
    }
}
