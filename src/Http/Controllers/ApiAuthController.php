<?php

namespace Landman\MultiTokenAuth\Http\Controllers;

use Illuminate\Support\Facades\Password;
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

    protected $shouldFireEvents;

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
     * @throws \Illuminate\Validation\ValidationException
     */
    public function login(Request $request)
    {
        $this->validate($request, $this->getLoginValidationRules());

        if ($this->requestHasInvalidClientId()) {
            return $this->invalidClientIdResponse();
        }

        if ($this->guard->attempt($request->only([$this->config['username'], 'password']), $request)) {
            $this->handleEvent($request, $this->guard->user(), 'afterApiLogin');

            if (class_exists("\\Illuminate\\Auth\\Events\\Login"))
                event(new \Illuminate\Auth\Events\Login($this->guard, $user, false));

            return $this->authenticationSuccessful($this->guard->user(), $this->guard->token());
        }

        return response()->json([
            'message' => trans('auth.failed')
        ], 401);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        $user = $this->guard->user();
        $this->guard->logout();
        $this->handleEvent($request, $user, 'afterApiLogout');

        if (class_exists("\\Illuminate\\Auth\\Events\\Logout"))
            event(new \Illuminate\Auth\Events\Logout($this->guard, $user));

        return response()->json(['success' => 1]);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function logoutAll(Request $request)
    {
        $user = $this->guard->user();
        $this->guard->logout();
        $count = $request->user()->invalidateAllTokens();
        $this->handleEvent($request, $user, 'afterApiLogout');

        if (class_exists("\\Illuminate\\Auth\\Events\\Logout"))
            event(new \Illuminate\Auth\Events\Logout($this->guard, $user));

        return response()->json(['count' => $count + 1, 'success' => 1]);
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
            return $this->invalidClientIdResponse();
        }

        $userData = $request->only($this->getRegistrationFields());

        if ($this->passwordRequiredForRegistration()) {
            $userData['password'] = bcrypt($request->input('password'));
        }

        try {
            DB::beginTransaction();

            $user = $this->user->fill($userData);
            $user = $this->handleEvent($request, $user, 'beforeApiRegistered');
            $user->save();
            $this->handleEvent($request, $user, 'afterApiRegistered');


            $this->guard->attempt($request->only([$this->config['username'], 'password']), $request);
            $this->handleEvent($request, $this->guard->user(), 'afterApiLogin');
            DB::commit();
            if (class_exists("\\Illuminate\\Auth\\Events\\Registered"))
                event(new \Illuminate\Auth\Events\Registered($user));

            if (class_exists("\\Illuminate\\Auth\\Events\\Login"))
                event(new \Illuminate\Auth\Events\Login($this->guard, $user, false));

            return $this->authenticationSuccessful($this->guard->user(), $this->guard->token());

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function user(Request $request)
    {
        return response()->json($request->user()->toApiFormat());
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
        $user = $user->toApiFormat();

        $token = $apiToken->token;
        if ($apiToken->expires_at !== null && !$apiToken->should_forget) {
//            $refresh_token = $apiToken->refresh_token;
            $expires_at = $apiToken->expires_at->toDateTimeString();
        }
        return response()->json(compact('user', 'token', 'refresh_token', 'expires_at'));
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $this->validate($request, [
            'password' => 'required|string|min:8|confirmed',
            'current_password' => 'required|string'
        ]);

        try {
            DB::beginTransaction();
            $userNameField = $this->config['username'];
            $credentials = [
                "{$userNameField}" => $user->{$userNameField},
                'password' => $request->input('current_password'),
            ];
            if ($this->guard->hasValidCredentials($user, $credentials)) {
                $user->update([
                    'password' => bcrypt($request->password)
                ]);
                $user->apiTokens()->delete();
                DB::commit();
                return response()->json(['message' => trans('Your password has been updated.')]);
            } else {
                return response()->json(['message' => trans('auth.failed')], 401);
            }
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e->getTraceAsString());
            return response()->json([
                'error' => $e->getMessage(),
                'message' => trans('errors.general'),
            ], 500);
        }
    }

    /**
     * @return array
     */
    private function getLoginValidationRules()
    {
        return $this->config['login_validation'];
    }

    /**
     * @return array
     */
    private function getRegisterValidationRules()
    {
        return $this->config['register_validation'];
    }

    /**
     * @return array
     */
    private function getRegistrationFields()
    {
        if ($this->config['register_usefillable']) {
            return $this->user->getFillable();
        }
        $fields = $this->config['register_fields'] ?? [];
        return count($fields) ? $fields : array_keys($this->config['register_validation']);
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

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    private function invalidClientIdResponse()
    {
        return response()->json(['message' => 'Invalid client id specified.'], 401);
    }

    /**
     * @param $request
     * @param $user
     * @param $eventName
     * @return bool
     */
    private function handleEvent(Request $request, $user, string $eventName)
    {
        if (method_exists($user, $eventName)) {
            $user = $user->{$eventName}($request);
        }
        return $user;
    }


    /**
     * Send a reset link to the given user.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function sendResetLinkEmail(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email',
        ]);

        if ($this->user->where('email', $request->input('email'))->count() === 0) {
            return response()->json([
                'message' => 'We can\'t find a user with that e-mail address.'
            ], 422);
        }

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $broker = Password::broker();
        $response = $broker->sendResetLink(
            $request->only('email')
        );

        return $response == Password::RESET_LINK_SENT
            ? response()->json([
                'message' => trans('passwords.sent')
            ])
            : response()->json(['message' => ''], 500);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function devices(Request $request)
    {
        return response()->json(
            $request->user()->apiTokens()->get([
                'id',
                'user_agent',
                'device',
                'updated_at',
            ])
        );

    }
}
