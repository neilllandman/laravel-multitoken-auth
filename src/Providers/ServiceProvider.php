<?php

namespace Landman\MultiTokenAuth\Providers;


use Illuminate\Support\ServiceProvider as SupportServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Landman\MultiTokenAuth\Auth\TokensGuard;
use Landman\MultiTokenAuth\Auth\TokensUserProvider;
use Landman\MultiTokenAuth\Console\Commands\DeleteClient;
use Landman\MultiTokenAuth\Console\Commands\MakeApiClient;
use Landman\MultiTokenAuth\Console\Commands\ListClients;
use Landman\MultiTokenAuth\Console\Commands\RefreshClient;


/**
 * Class AuthServiceProvider
 * @package App\Providers
 */
class ServiceProvider extends SupportServiceProvider
{
    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        // add api user provider.
        Auth::provider('token-users', function ($app, array $config) {
            return new TokensUserProvider($app->make(Config::get('multipletokens.model')));
        });

        // add api guard.
        Auth::extend('multi-tokens', function ($app, $name, array $config) {
            return new TokensGuard(
                new TokensUserProvider($app->make(Config::get('multipletokens.model'))),
                $app->make('request'),
                'api_token',
                'api_token',
                ['access-api']
            );
        });

        // Load migrations.
        $this->loadMigrationsFrom(__DIR__ . '/../../migrations');

        // Register routes.
        $config = Config::get('multipletokens');
        Route::prefix($config['route_prefix'])
            ->middleware($config['route_middleware'])
            ->namespace("Landman\\MultiTokenAuth\\Http\\Controllers")
            ->group(__DIR__ . '/../../routes/api.php');

        // Publish files.
        $this->publishes([
            __DIR__ . '/../../config/multipletokens.php' => config_path('multipletokens.php'),
        ]);

        // Register commands.
        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeApiClient::class,
                DeleteClient::class,
                RefreshClient::class,
                MakeApiClient::class,
                ListClients::class,
            ]);
        }
    }

    /**
     *
     */
    public function register()
    {

        $this->mergeConfigFrom(
            __DIR__ . '/../../config/multipletokens.php', 'multipletokens'
        );

//        Auth::resolveUsersUsing(function ($guard = null) {
//            return Auth::user() ? Auth::user() : Auth::guard('api')->user();
//        });
    }
}
