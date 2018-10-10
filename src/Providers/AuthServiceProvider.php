<?php

namespace Landman\MultiTokenAuth\Providers;

use App\Services\Auth\TokensGuard;
use App\Services\Auth\TokensUserProvider;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;


/**
 * Class AuthServiceProvider
 * @package App\Providers
 */
class AuthServiceProvider extends ServiceProvider
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
            return new TokensUserProvider($app->make('App\User'));
        });

        // add custom api guard
        Auth::extend('multi-tokens', function ($app, $name, array $config) {
            return new TokensGuard(
                Auth::createUserProvider($config['provider']),
                $app->make('request'),
                'api_token',
                'api_token',
                ['access-api']
            );
        });


        $this->loadMigrationsFrom(__DIR__ . '/../migrations');
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
    }

    /**
     *
     */
    public function register()
    {
        Auth::resolveUsersUsing(function ($guard = null) {
            return Auth::user() ? Auth::user() : Auth::guard('api')->user();
        });
        parent::register();
    }
}
