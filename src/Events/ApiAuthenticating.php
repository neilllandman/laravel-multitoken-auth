<?php

namespace Landman\MultiTokenAuth\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Landman\MultiTokenAuth\Models\ApiToken;

/**
 * Class ApiAuthenticating
 * @package Landman\MultiTokenAuth\Events
 */
class ApiAuthenticating
{
    use SerializesModels;

    /** @var ApiToken|null */
    public $token;

    /**
     * Create a new event instance.
     * @param ApiToken|null $token
     * @return void
     */
    public function __construct(ApiToken $token = null)
    {
        $this->token = $token;
    }
}
