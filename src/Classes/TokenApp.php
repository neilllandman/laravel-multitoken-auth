<?php
/**
 * Created by PhpStorm.
 * User: neill
 * Date: 2018/10/12
 * Time: 8:41 PM
 */

namespace Landman\MultiTokenAuth\Classes;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Landman\MultiTokenAuth\Auth\TokensGuard;
use Landman\MultiTokenAuth\Events\ApiAuthenticated;
use Landman\MultiTokenAuth\Events\ApiLogin;
use Landman\MultiTokenAuth\Events\ApiLogout;
use Landman\MultiTokenAuth\Models\ApiClient;
use Landman\MultiTokenAuth\Models\ApiToken;

/**
 * Class MultiTokenAuth
 */
class TokenApp
{

//    public static $config;

    /**
     *
     */
    const CONFIG_SPACE = 'multipletokens';

    /**
     * @var bool
     */
    public static $shouldFireEvents = false;

    /**
     *
     */
    public function boot()
    {
        self::$shouldFireEvents = true;
    }

    /**
     * @param string|null $config
     * @param null $default
     * @return mixed
     */
    public static function config(string $config = null, $default = null)
    {
        $configString = self::CONFIG_SPACE;
        if ($config) {
            $configString = self::CONFIG_SPACE . ".{$config}";
        }
        return \Illuminate\Support\Facades\Config::get($configString, $default);
    }


    /**
     * Fire the authenticating event.
     *
     * @param TokensGuard $guard
     * @param ApiToken|null $token
     * @return void
     */
    public static function fireAuthenticatingEvent(TokensGuard $guard, ApiToken $token = null)
    {
        if (self::$shouldFireEvents === true) {
            event(new ApiAuthenticating($guard, $guard->user(), $guard->token()));
        }
    }


    /**
     * Fire the authenticated event.
     *
     * @param TokensGuard $guard
     * @param  \Illuminate\Contracts\Auth\Authenticatable $user
     * @return void
     */
    public static function fireAuthenticatedEvent(TokensGuard $guard, $user)
    {
        if (self::$shouldFireEvents === true && self::$authenticationFired === false) {
            self::$authenticationFired = true;
//            event(new \Illuminate\Auth\Events\Authenticated($guard, $user));
            event(new ApiAuthenticated($guard, $guard->user(), $guard->token()));
        }
    }

    /**
     * Fire the login event.
     *
     * @param TokensGuard $guard
     * @param $user
     * @return void
     */
    public static function fireLoginEvent(TokensGuard $guard, $user)
    {
        if (self::$shouldFireEvents === true) {
//            event(new \Illuminate\Auth\Events\Login($guard, $guard->user, false));
            event(new ApiLogin($guard, $guard->user(), $guard->token()));
        }
    }

    /**
     * Fire the logout event.
     *
     * @param TokensGuard $guard
     * @param  \Illuminate\Contracts\Auth\Authenticatable $user
     * @return void
     */
    public static function fireLogoutEvent(TokensGuard $guard, $user)
    {
        if (self::$shouldFireEvents === true) {
//            event(new \Illuminate\Auth\Events\Logout($guard, $guard->user()));
            event(new ApiLogout($guard, $guard->user(), $guard->token()));
        }
    }


    /**
     * Fire the registered event.
     *
     * @param TokensGuard $guard
     * @param  \Illuminate\Contracts\Auth\Authenticatable $user
     * @return void
     *
     */
    public static function fireRegisterEvent(TokensGuard $guard, $user)
    {
        if (self::$shouldFireEvents === true) {
            //            event(new \Illuminate\Auth\Events\Registered($user));
            event(new ApiRegistered($guard, $guard->user(), $guard->token()));
        }
    }

    /**
     * @return string
     */
    public static function getUserClass()
    {
        return self::config('model');
    }

    /**
     * @return Authenticatable
     */
    public static function makeUserModel()
    {
        return app()->make(self::config('model'));
    }

    /**
     * @param $routeName
     * @return mixed
     */
    public static function routeUri($routeName)
    {
        $route = self::config('route_prefix') . "/" . self::config('route_mappings.' . $routeName);
        return str_replace('//', '/', $route);
    }

    /**
     * @param string $name
     * @return ApiClient
     */
    public static function makeClient(string $name): ApiClient
    {
        return ApiClient::make($name);
    }

    /**
     * @param string $clientId
     * @return bool
     */
    public static function validateClientId(string $clientId): bool
    {
        $clientIds = ApiClient::pluck('value')->toArray();

        if (App::environment() === 'local' && !empty(env('API_TEST_CLIENT_ID'))) {
            $clientIds[] = env('API_TEST_CLIENT_ID');
        }

        return in_array($clientId, $clientIds);
    }

    /**
     * @return TokensGuard
     */
    public static function guard()
    {
        return Auth::guard(self::config('guard_name'));
    }
}
