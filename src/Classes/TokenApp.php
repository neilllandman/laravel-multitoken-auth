<?php
/**
 * Created by PhpStorm.
 * User: neill
 * Date: 2018/10/12
 * Time: 8:41 PM
 */

namespace Landman\MultiTokenAuth\Classes;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Landman\MultiTokenAuth\Auth\TokensGuard;
use Landman\MultiTokenAuth\Events\ApiAuthenticated;
use Landman\MultiTokenAuth\Events\ApiAuthenticating;
use Landman\MultiTokenAuth\Events\ApiLogin;
use Landman\MultiTokenAuth\Events\ApiLogout;
use Landman\MultiTokenAuth\Events\ApiRegistered;
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
     * @var array
     */
    public static $defaultConfig = [];

    /**
     * @var array
     */
    public static $config = [];
    /**
     * @var bool
     */
    public static $test = false;
    /**
     * @var
     */
    private static $booted;


    /**
     * @var bool
     */
    public static $shouldFireEvents = true;

    /**
     *
     */
    public static function test()
    {
        self::$test = true;
    }

    /**
     * @return void
     */
    public static function boot()
    {
        self::makeConfig();
        self::$shouldFireEvents = true;
        self::$booted = true;
        if (app()->environment() === 'testing') {
            self::test();
        }
    }

    /**
     * @return void
     */
    private static function makeConfig()
    {
        self::$defaultConfig = require(__DIR__ . "/../../config/multipletokens.php");
        if (app()->environment() !== 'testing') {
            // @codeCoverageIgnoreStart
            self::$config = Config::get(self::CONFIG_SPACE);
            // @codeCoverageIgnoreEnd
        } else {
            self::$config = self::$defaultConfig;
            self::$config['send_verification_email'] = true;
        }
        self::mergeRouteMappings();
    }

    /**
     * @return void
     */
    private static function mergeRouteMappings()
    {
        $routeMappings = array_filter(
            array_merge(
                self::$defaultConfig['route_mappings'],
                self::$config['route_mappings'] ?? []
            )
        );
        self::$config['route_mappings'] = $routeMappings;
    }

    /**
     * @param string|null $config
     * @param null $default
     * @return mixed
     */
    public static function config(string $config = null, $default = null)
    {
        if ($config) {
            return array_get(self::$config, $config) ?? $default;
        }
        return self::$config;
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
        if (self::$shouldFireEvents === true) {
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
     * @return void
     *
     */
    public static function fireRegisterEvent(TokensGuard $guard)
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
     * @param null|string $clientId
     * @param bool $errorOnFail
     * @return bool
     * @throws AuthenticationException
     */
    public static function validateClientId(?string $clientId, bool $errorOnFail = false): bool
    {
        if ($clientId === null) {
            return false;
        }
        $clientIds = ApiClient::pluck('value')->toArray();

        if (App::environment() !== 'production' && !empty(env('API_TEST_CLIENT_ID'))) {
            $clientIds[] = env('API_TEST_CLIENT_ID');
        }

        $valid = in_array($clientId, array_filter($clientIds));
        if ($errorOnFail) {
            throw new AuthenticationException("Invalid client id.");
        }
        return $valid;
    }

    /**
     * @return TokensGuard
     */
    public static function guard()
    {
        return Auth::guard(self::config('guard_name'));
    }
}
