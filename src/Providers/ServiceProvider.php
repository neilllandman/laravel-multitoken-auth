<?php

namespace Landman\MultiTokenAuth\Providers;


use Illuminate\Foundation\Support\Providers\AuthServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Landman\MultiTokenAuth\Auth\TokensGuard;
use Landman\MultiTokenAuth\Auth\TokensUserProvider;
use Landman\MultiTokenAuth\Console\Commands\MakeApiClient;
use Landman\MultiTokenAuth\Console\Commands\ListClients;


/**
 * Class AuthServiceProvider
 * @package App\Providers
 */
class ServiceProvider extends AuthServiceProvider
{
    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        // add custom api guard provider
        Auth::provider('token-users', function ($app, array $config) {
            return new TokensUserProvider($app->make(Config::get('multipletokens.model')));
        });

        // add custom api guard
        Auth::extend('multi-tokens', function ($app, $name, array $config) {
            return new TokensGuard(
                new TokensUserProvider($app->make(Config::get('multipletokens.model'))),
                $app->make('request'),
                'api_token',
                'api_token',
                ['access-api']
            );
        });

        $this->loadMigrationsFrom(__DIR__ . '/../migrations');
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');

        $this->publishes([
            __DIR__ . '/../../config/multipletokens.php' => config_path('multipletokens.php'),
        ]);


        if ($this->app->runningInConsole()) {
            $this->commands([
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
        parent::register();
    }
}
