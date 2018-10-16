<?php

namespace Landman\MultiTokenAuth\Providers;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Landman\MultiTokenAuth\Auth\TokensGuard;
use Landman\MultiTokenAuth\Classes\TokenApp;
use Landman\MultiTokenAuth\Events\ApiAuthenticated;
use Landman\MultiTokenAuth\Events\ApiAuthenticating;
use Landman\MultiTokenAuth\Events\ApiLogin;
use Landman\MultiTokenAuth\Events\ApiRegistered;
use Landman\MultiTokenAuth\Models\ApiToken;

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
//    protected $listen = [
////        Registered::class => [
////            SendEmailVerificationNotification::class,
////        ],
//    ];

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
            if (ApiToken::shouldExpire()) {
                $token = $event->guard->token();
                if ($token) {
                    if ($token->hasExpired()) {
                        $token->invalidate();
                        throw new AuthenticationException("Unauthenticated - your token has expired and has been invalidated.");
                    }
                }
            }
        });
        Event::listen(ApiAuthenticated::class, function (ApiAuthenticated $event) {
            if (ApiToken::shouldAutoRefresh())
                $event->guard->token()->updateExpiresAt();
        });

        Event::listen(ApiLogin::class, function (ApiLogin $event) {
        });

        Event::listen(ApiLogout::class, function (ApiLogout $event) {
        });


        Event::listen(ApiRegistered::class, function (ApiRegistered $event) {
            if (TokenApp::config('send_verification_email')) {
                if ($event->user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !$event->user->hasVerifiedEmail()) {
                    $event->user->sendEmailVerificationNotification();
                }
            }
        });

        parent::boot();
    }
}
