<?php

namespace Landman\MultiTokenAuth\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Queue\SerializesModels;
use Landman\MultiTokenAuth\Auth\TokensGuard;
use Landman\MultiTokenAuth\Models\ApiToken;

/**
 * Class ApiAuthenticating
 * @package Landman\MultiTokenAuth\Events
 */
class BaseApiEvent
{
    use SerializesModels;

    /** @var TokensGuard */
    public $guard;

    /** @var Authenticatable */
    public $user;

    /** @var ApiToken */
    public $token;

    /**
     * ApiLogout constructor.
     * @param TokensGuard $guard
     * @param Authenticatable $user
     * @param ApiToken $token
     */
    public function __construct(TokensGuard $guard, Authenticatable $user, ApiToken $token = null)
    {
        $this->guard = $guard;
        $this->user = $user;
        $this->token = $token;
    }
}
