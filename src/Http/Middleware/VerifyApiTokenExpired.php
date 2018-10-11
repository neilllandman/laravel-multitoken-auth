<?php

namespace Landman\MultiTokenAuth\Http\Middleware;

use App\UserObserver;
use Carbon\Carbon;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Session;

class VerifyApiTokenExpired
{

    private $guard;

    function __construct()
    {
        $this->guard = Auth::guard('api');
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $guard = $this->guard;
        if ($guard->check()) {
            $user = $guard->user();
            $token = $guard->token();
            if ($token) {
                if ($token->expires_at !== null && $token->expires_at->lt(now())) {
                    $token->expire();
                    return response()->json([
                        'message' => Lang::get('auth.expired')
                    ], 419);
                } else {
                    $token->updateExpiresAt();
                }
            }
        }
        return $next($request);
    }
}
