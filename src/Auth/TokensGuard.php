<?php

namespace Landman\MultiTokenAuth\Auth;

use Illuminate\Auth\AuthenticationException;
use Landman\MultiTokenAuth\Events\ApiAuthenticated;
use Landman\MultiTokenAuth\Events\ApiAuthenticating;
use Landman\MultiTokenAuth\Events\ApiLogin;
use Landman\MultiTokenAuth\Models\ApiToken;
use Illuminate\Auth\TokenGuard;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use Landman\MultiTokenAuth\Classes\TokenApp;

/**
 * Created by PhpStorm.
 * User: neilllandman
 * Date: 2018/03/07
 * Time: 08:27
 *
 * @method user()
 */
class TokensGuard extends TokenGuard
{

    /**
     * The event dispatcher instance.
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected $events;

    protected static $authenticationFired = false;

    /**
     * @var string
     */
    protected $token;

    /**
     * Create a new authentication guard.
     *
     * @param  \Illuminate\Contracts\Auth\UserProvider $provider
     * @param  \Illuminate\Http\Request $request
     * @param  string $inputKey
     * @param  string $storageKey
     * @return void
     */
    public function __construct(UserProvider $provider, Request $request, $inputKey = 'api_token', $storageKey = 'api_token')
    {
        $this->shouldFireEvents = true;
        parent::__construct($provider, $request, $inputKey, $storageKey);
    }

    /**
     * Attempt to authenticate a user using the given credentials.
     *
     * @param  array $credentials
     * @param  Request $request
     * @return bool
     */
    public function attempt(array $credentials = [], Request $request = null)
    {
        $this->lastAttempted = $user = $this->provider->retrieveByCredentials($credentials);
        if ($this->hasValidCredentials($user, $credentials)) {
//            TokenApp::fireAuthenticatedEvent($this,$user);
            $this->login($user, $request);
            return true;
        }
        return false;
    }

    /**
     * Log a user into the application.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable $user
     * @param Request $request
     */
    public function login(Authenticatable $user, Request $request)
    {
        $user_agent = $request->header('user-agent') ?? 'Unknown';
        $device = $request->input('device') ?? 'Unknown';

        $token = new ApiToken(compact('user_agent', 'device'));
        $token->setExpiresAt();
        $user->apiTokens()->save($token);
        $this->setUser($user);
        TokenApp::fireLoginEvent($this, $user);
        $this->setToken($token);
    }

    /**
     * Validate a user's credentials.
     *
     * @param  array $credentials
     * @return bool
     */
    public function validate(array $credentials = [])
    {
        if (empty($credentials[$this->inputKey])) {
            return false;
        }
        $credentials = [$this->storageKey => $credentials[$this->inputKey]];
        if ($this->provider->retrieveByCredentials($credentials)) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user matches the credentials.
     *
     * @param  mixed $user
     * @param  array $credentials
     * @return bool
     */
    protected function hasValidCredentials($user, $credentials)
    {
        return !is_null($user) && $this->provider->validateCredentials($user, $credentials);
    }

    /**
     * @return ApiToken
     * @throws \Exception
     */
    public function logout()
    {
        $user = $this->user();
        if ($user) {
            $this->token()->invalidate();
            session()->invalidate();
            TokenApp::fireLogoutEvent($this, $user);
        }
        return $this->token();
    }

    /**
     * @return ApiToken
     */
    public function logoutAll()
    {
        $user = $this->user();
        if ($user) {
            $this->user->invalidateAllTokens();
            TokenApp::fireLogoutEvent($this, $user);
        }
        return $this->token();
    }

    /**
     * Determine if the current user is authenticated.
     *
     * @return bool
     */
    public function check()
    {
        $check = !is_null($this->user());
        TokenApp::fireAuthenticatingEvent($this, $this->token());
        if ($check) {
            TokenApp::fireAuthenticatedEvent($this, $this->user());
        }
        return $check;
    }

    /**
     * @return ApiToken
     */
    public function token()
    {
        if (!$this->token) {
            if ($this->user) {
                $this->token = $this->user->apiTokens()
                    ->where('token', $this->getTokenForRequest())
                    ->first();
            }
        }
        return $this->token;
    }

    /**
     * @param string $token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }
}
