<?php

namespace Landman\MultiTokenAuth\Http\Controllers;

use App\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Password;
use Landman\MultiTokenAuth\Auth\TokensGuard;
use Landman\MultiTokenAuth\Classes\TokenApp;
use Landman\MultiTokenAuth\Events\ApiRegistered;
use Landman\MultiTokenAuth\Models\ApiToken;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Landman\MultiTokenAuth\Models\ApiClient;
use Landman\MultiTokenAuth\Traits\ValidatesClientId;

/**
 * Class ApiAuthController
 * @package Landman\MultiTokenAuth
 * @property TokensGuard $guard
 */
class ApiAuthController extends Controller
{

    use \Illuminate\Foundation\Validation\ValidatesRequests;
    use \Illuminate\Foundation\Auth\Access\AuthorizesRequests;
    use ValidatesClientId;

    /**
     * @var mixed
     */
    protected $guard;

    /** @var array */
    protected $config;

    /**
     * @var
     */
    protected $shouldFireEvents;

    /**
     * ApiAuthController constructor.
     * @param Request $request
     */
    function __construct(Request $request)
    {
        $this->guard = TokenApp::guard();
        $this->config = TokenApp::config();
        $this->user = TokenApp::makeUserModel();
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\AuthenticationException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function login(Request $request)
    {
        $this->validateClientId();
        
        $this->validate($request, $this->getLoginValidationRules());


        if ($this->guard->attempt($request->only([$this->config['username'], 'password']), $request)) {
            $this->handleEvent($request, $this->guard->user(), 'afterApiLogin');

            return $this->guard->authenticatedResponse();
        }

        return response()->json([
            'message' => trans('auth.failed')
        ], 401);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function logout(Request $request)
    {
        $user = $this->guard->user();
        $this->guard->logout();
        $this->handleEvent($request, $user, 'afterApiLogout');

        return response()->json(['success' => 1]);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function logoutAll(Request $request)
    {
        $user = $this->guard->user();
        $this->guard->logoutAll();
        $this->handleEvent($request, $user, 'afterApiLogout');

        return response()->json(['success' => 1]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function register(Request $request)
    {
        $this->validate($request, $this->getRegisterValidationRules());

        $this->validateClientId();

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

            $this->guard->login($user);

            DB::commit();
            TokenApp::fireRegisterEvent($this->guard);
            return $this->guard->authenticatedResponse(true);

            // @codeCoverageIgnoreStart
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e);
            // @codeCoverageIgnoreEnd
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
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Exception
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
            $usernameField = $this->config['username'];
            $passwordField = $this->config['password_field'];
            $credentials = [
                "{$usernameField}" => $user->{$usernameField},
                'password' => $request->input('current_password'),
            ];
            if ($this->guard->getProvider()->validateCredentials($user, $credentials)) {
                $user->update([
                    'password' => bcrypt($request->password)
                ]);
                $user->invalidateAllTokens($this->guard->token());
                DB::commit();
                return response()->json(['message' => trans('Your password has been updated.')]);
            } else {
                return response()->json(['message' => trans('auth.failed')], 401);
            }
            // @codeCoverageIgnoreStart
        } catch (\Exception $e) {
            DB::rollback();
            return $this->errorResponse($e);
        }
        // @codeCoverageIgnoreEnd
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
     * @param $user
     * @param $eventName
     * @return Authenticatable
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
     * @throws AuthenticationException
     * @throws \Exception
     */

    public function sendResetLinkEmail(Request $request)
    {
        $this->validate($request, [
            'client_id' => 'required',
            'email' => 'required|email',
        ]);

        $this->validateClientId();

        $username = TokenApp::config('username');
        $user = User::where([
            $username => $request->input($username),
        ])->first();


        // @codeCoverageIgnoreStart
        if (!$user) {
            return response()->json([
                'message' => 'We can\'t find a user with that e-mail address.'
            ], 422);
        }
        // @codeCoverageIgnoreEnd

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.

        try {
            DB::beginTransaction();
            $resetToken = Password::createToken($user);
            $user->sendPasswordResetNotification($resetToken);

            DB::commit();
            return response()->json([
                'message' => trans('passwords.sent')
            ]);
            // @codeCoverageIgnoreStart
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e);

        }
        // @codeCoverageIgnoreEnd
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
                'created_at',
            ])
        );
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function refreshToken(Request $request)
    {
        try {
            $this->guard->token()->refreshToken();
            return $this->guard->authenticatedResponse();
            // @codeCoverageIgnoreStart
        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function devicesLogout(Request $request, string $id)
    {
        $apiToken = ApiToken::withTrashed()->find($id);
        // @codeCoverageIgnoreStart
        if (!$apiToken) {
            return response()->json(['message' => 'Invalid id.'], 422);
        }
        // @codeCoverageIgnoreEnd
        try {
            $apiToken->invalidate();
            return response()->json(['success' => 1]);
            // @codeCoverageIgnoreStart
        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * @param \Exception $e
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    private function errorResponse(\Exception $e)
    {
        if (config('app.debug', false)) {
            throw $e;
        } else {
            return response()->json(['message' => 'Server error.', 'code' => $e->getCode() ?? 500], 500);
        }
    }
}
