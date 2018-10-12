<?php

namespace Landman\MultiTokenAuth\Providers;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Password;
use Landman\MultiTokenAuth\Auth\TokensGuard;
use Landman\MultiTokenAuth\Events\ApiAuthenticating;

/**
 * Class EventServiceProvider
 * @package App\Providers
 */
class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
//        Registered::class => [
//            SendEmailVerificationNotification::class,
//        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        Event::listen(PasswordReset::class, function (PasswordReset $event) {
            if (method_exists($event->user, 'invalidateAllTokens')) {
                $event->user->invalidateAllTokens();
            }
        });

        Event::listen(ApiAuthenticating::class, function (ApiAuthenticating $event) {
            $token = $event->token;
            if ($token) {
                if ($token->expires_at !== null && $token->expires_at->lt(now())) {
                    $token->invalidate();
                    throw new AuthenticationException("Unauthenticated - your token has expired and has been invalidated.");
                }
            }
        });

        Event::listen(Authenticated::class, function (Authenticated $event) {
            if ($event->guard instanceof TokensGuard) {

                $event->guard->token()->updateExpiresAt();
            }
        });

        parent::boot();
    }
}
