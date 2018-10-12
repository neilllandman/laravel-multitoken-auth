<?php

namespace Landman\MultiTokenAuth\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Landman\MultiTokenAuth\Auth\TokensGuard;

/**
 * Class ApiAuthenticating
 * @package Landman\MultiTokenAuth\Events
 */
class ApiAuthenticated
{
    use SerializesModels;
    /** @var TokensGuard */
    public $guard;

    /**
     * ApiAuthenticated constructor.
     * @param TokensGuard $guard
     */
    public function __construct(TokensGuard $guard)
    {
        $this->guard = $guard;
    }
}
