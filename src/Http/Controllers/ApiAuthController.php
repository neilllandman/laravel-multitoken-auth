<?php

namespace Landman\MultiTokenAuth\Http\Controllers;

use Landman\MultiTokenAuth\Models\ApiToken;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Landman\MultiTokenAuth\Models\ApiClient;

/**
 * Class ApiAuthController
 * @package Landman\MultiTokenAuth
 */
class ApiAuthController extends Controller
{

    use \Illuminate\Foundation\Validation\ValidatesRequests;
    use \Illuminate\Foundation\Auth\Access\AuthorizesRequests;

    /**
     * @var mixed
     */
    protected $guard;

    /** @var array */
    protected $config;

    /**
     * ApiAuthController constructor.
     * @param Request $request
     */
    function __construct(Request $request)
    {
        $this->guard = Auth::guard('api');
        $this->config = Config::get('multipletokens');
        $this->user = app()->make($this->config['model']);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $this->validate($request, $this->getLoginValidationRules());

        if ($this->requestHasInvalidClientId()) {
            return response()->json(['message' => Lang::get('auth.failed')], 401);
        }

        $remember = $request->has('remember') && $request->input('remember');
        if ($this->guard->attempt($request->only([$this->config['username'], 'password']), $remember)) {
            return $this->authenticationSuccessful($this->guard->user(), $this->guard->token());
        }

        return response()->json(['message' => Lang::get('auth.failed')], 401);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        $this->guard->logout();
        return response()->json(['success' => 1]);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function logoutAll(Request $request)
    {
        $this->guard->logout();
        $count = $request->user()->apiTokens()->delete();
        return response()->json(['count' => $count, 'success' => 1]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function register(Request $request)
    {
        $this->validate($request, $this->getRegisterValidationRules());

        if ($this->requestHasInvalidClientId()) {
            return response()->json(['message' => Lang::get('auth.failed')], 401);
        }

        $userData = $request->only($this->getRegistrationFields());

        if ($this->passwordRequiredForRegistration()) {
            $userData['password'] = bcrypt($request->input('password'));
        }
        try {
            DB::beginTransaction();
            $user = $this->user->create($userData);
            DB::commit();
            if ($this->guard->attempt($request->only([$this->config['username'], 'password']))) {
                return $this->authenticationSuccessful($this->guard->user(), $this->guard->token());
            }
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }

    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function user(Request $request)
    {
        return response()->json($request->user());
    }

    /**
     * Build response for successful login, registration and token refresh
     *
     * @param $user
     * @param ApiToken $apiToken
     * @return \Illuminate\Http\JsonResponse
     */
    private function authenticationSuccessful($user, ApiToken $apiToken)
    {
        $user->makeHidden(['deleted_at']);

        $token = $apiToken->token;
        if ($apiToken->expires_at !== null && !$apiToken->should_forget) {
            $refresh_token = $apiToken->refresh_token;
            $expires_at = $apiToken->expires_at;
        }
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

//    /**
//     * @param Request $request
//     * @return \Illuminate\Http\JsonResponse
//     */
//    public function refreshToken(Request $request)
//    {
//        $this->validate($request, [
//            'grant_type' => 'required',
//            'refresh_token' => 'required',
//        ]);
//        if ($request->input('grant_type') === 'refresh_token') {
//            $user = User::find($request->input('user_id'));
//            if ($user) {
//                $token = null;
//                $user->apiTokens()->withTrashed()->latest()->each(function ($t) use (&$token, $request) {
//                    if ($t->equalsEncryptedAttribute('refresh_token', $request->input('refresh_token'))) {
//                        $token = $t;
//                        return false;
//                    }
//                });
//                if ($token) {
//                    $token->refresh();
//                    return $this->authenticationSuccessful($user, $token);
//                } else {
//                    $message = "Invalid refresh token.";
//                }
//            } else {
//                $message = Lang::get('auth.failed');
//            }
//        } else {
//            $message = Lang::get('auth.unsupported_grant_type') . " " . $request->input('grant_type') . ".";
//        }
//        return response()->json(compact('message'), 422);
//    }

    /**
     * @return array
     */
    private function getLoginValidationRules()
    {
        return $this->config['login']['validation'];
    }

    /**
     * @return array
     */
    private function getRegisterValidationRules()
    {
        return $this->config['register']['validation'];
    }

    /**
     * @return array
     */
    private function getRegistrationFields()
    {
        $fields = $this->config['register']['fields'] ?? [];
        return count($fields) ? $fields : array_keys($this->config['register']['validation']);
    }

    /**
     * @return bool
     */
    private function passwordRequiredForRegistration()
    {
        return in_array('password', $this->getRegistrationFields());
    }

    /**
     * @param $request
     * @return bool
     */
    private function requestHasValidClientId($request = nulll)
    {
        $request = $request ?? request();

        $clientIds = ApiClient::pluck('value')->toArray();
        return in_array($request->input('client_id'), $clientIds);
    }

    /**
     * @param null $request
     * @return bool
     */
    private function requestHasInvalidClientId($request = null)
    {
        $request = $request ?? request();
        return $this->requestHasValidClientId($request) === false;
    }
}
