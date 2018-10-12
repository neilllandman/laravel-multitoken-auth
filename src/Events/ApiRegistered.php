<?php

namespace Landman\MultiTokenAuth\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Landman\MultiTokenAuth\Auth\TokensGuard;

/**
 * Class ApiAuthenticating
 * @package Landman\MultiTokenAuth\Events
 */
class ApiRegistered
{
    use SerializesModels;
    /** @var TokensGuard */
    public $user;

    /**
     * ApiAuthenticated constructor.
     * @param Authenticatable $user
     */
    public function __construct(Authenticatable $user)
    {
        $this->user = $user;
    }
}
