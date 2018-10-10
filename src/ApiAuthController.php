<?php

namespace Landman\MultiTokenAuth;

use App\ApiToken;
use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;

/**
 * Class ApiAuthController
 * @package Landman\MultiTokenAuth
 */
class ApiAuthController extends Controller
{
    /**
     * @var mixed
     */
    protected $guard;

    /**
     * ApiAuthController constructor.
     * @param Request $request
     */
    function __construct(Request $request)
    {
        $this->guard = Auth::guard('api');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $this->validate($request, [
            'client_id' => 'required|string',
            'email' => 'required|email|string',
            'password' => 'required|string',
        ]);

        if ($request->input('client_id') === config('auth.api_client_id')) {
            $remember = $request->has('remember') && $request->input('remember');
            if ($this->guard->attempt($request->only(['email', 'password']), $remember)) {
                return $this->authenticationSuccessful($this->guard->user(), $this->guard->token());
            }
        }

        return response()->json(['message' => Lang::get('auth.failed')], 401);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        Auth::guard('api')->logout();
        return response()->json(['success' => 1]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function ping(Request $request)
    {
        return response()->json(['message' => 'Pong', 'body' => request()->input(), 'headers' => request()->header()]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::create([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => bcrypt($request->input('password')),
        ]);

        if ($this->guard->attempt($request->only(['email', 'password']), $remember)) {
            return $this->authenticationSuccessful($this->guard->user(), $this->guard->token());
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function refreshToken(Request $request)
    {
        $this->validate($request, [
            'grant_type' => 'required',
            'refresh_token' => 'required',
        ]);
        if ($request->input('grant_type') === 'refresh_token') {
            $user = User::find($request->input('user_id'));
            if ($user) {
                $token = null;
                $user->apiTokens()->withTrashed()->latest()->each(function ($t) use (&$token, $request) {
                    if ($t->equalsEncryptedAttribute('refresh_token', $request->input('refresh_token'))) {
                        $token = $t;
                        return false;
                    }
                });
                if ($token) {
                    $token->refresh();
                    return $this->authenticationSuccessful($user, $token);
                } else {
                    $message = "Invalid refresh token.";
                }
            } else {
                $message = Lang::get('auth.failed');
            }
        } else {
            $message = Lang::get('auth.unsupported_grant_type') . " " . $request->input('grant_type') . ".";
        }
        return response()->json(compact('message'), 422);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function user(Request $request)
    {
        $user = $request->user();
        $user = $user;
        $user->setHidden([
            'password', 'admin_login_allowed_expiry', 'remember_token',
            'deleted_at', 'created_at', 'updated_at'
        ]);
        if ($request->has('with_permissions')) {
            $user->setPrettyPermissions();
            $user->append('abilities');
        }
        if ($request->has('with_gym')) {
            $user->load(['gym']);
        }
        $user->append(['role_name']);
        return response()->json($user);
    }

    /**
     * Build response for successful login, registration and token refresh
     *
     * @param User $user
     * @param ApiToken $apiToken
     * @return \Illuminate\Http\JsonResponse
     */
    private function authenticationSuccessful(User $user, ApiToken $apiToken)
    {
        $user->makeHidden(['deleted_at']);
        $this->guard->user()->update(['last_login_at' => now()->toDateTimeString()]);
        $token = $apiToken->token;
        if ($apiToken->expires_at !== null && !$apiToken->should_forget) {
            $refresh_token = $apiToken->refresh_token;
            $expires_at = $apiToken->expires_at;
        }
        $user->append(['role_name']);
        return response()->json(compact('user', 'token', 'refresh_token', 'expires_at'));
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $this->validate($request, [
            'password' => 'required|string|min:6|confirmed',
        ]);

        DB::beginTransaction();

        try {
            $user->update([
                'password' => bcrypt($request->password)
            ]);
            DB::commit();
            return response()->json(['message' => Lang::get('passwords.updated')]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e->getTraceAsString());
            return response()->json([
                'error' => $e->getMessage(),
                'message' => Lang::get('errors.general'),
            ], 500);
        }
    }
}


