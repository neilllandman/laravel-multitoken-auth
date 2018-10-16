<?php

return [

    /*
     * The Eloquent model class to use.
     */
    'model' => 'App\\User',

    /*
     * Eloquent model username column. This column will be used for
     * authentication on login.
     */
    'username' => 'email',


    /*
     * Eloquent model password column. This column will be used for
     * authentication on login.
     */
    'password_field' => 'password',

    /*
     * The names of the tables created when running the migrations.
     */
    'table_clients' => 'api_clients',
    'table_tokens' => 'api_tokens',

    /*
     * Login configurations.
     */
    /*
     * Validation rules to use upon login. Note that client_id is always validated.
     */
    'login_validation' => [
        'email' => 'required|email|string',
        'password' => 'required|string',
    ],


    /*
     * Register configuration.
     */
    /*
     * The fields that will be passed to the create method of the given Eloquent model. Note
     * that if the password field is present it will be encrypted using bcrypt().
     * The keys in the validation array (see below) is used by default, but you
     * can uncomment this line if need be.
     */

//        'register_fields' => ['name', 'email', 'password'],

    /*
     * You can also specify the fields passed to be the fillable array declared on your User model.
     */
    'register_usefillable' => false,

    /*
     * Validation rules to use upon registration. If the 'fields' array above
     * is not given, the keys for this array will be used.
     */
    'register_validation' => [
        'name' => 'required|string|min:2',
        'email' => 'required|email|string|unique:users',
        'password' => 'required|string|min:8|confirmed',
    ],

    /*
     * Whether or not to send an email verification mail to the user on registration.
     * Refer to https://laravel.com/docs/5.7/verification.
     */
    'send_verification_email' => false,


    /*
     * Route configurations.
     */

    /* Middleware applied to all routes. */
    'route_middleware' => ['api'],

    /* Prefix for all routes */
    'route_prefix' => 'api',

    /*
     * Mappings. If you would like to change the default route paths, you can do that here. If you
     * would like to disable any of these routes, you can set them equal to null individually.
     */
    'route_mappings' => [
        'login' => '/auth/login',
        'register' => '/auth/register',
        'user' => '/auth/user',
        'devices' => '/auth/user/api-devices',
        'logout' => '/auth/logout',
        'logout_all' => '/auth/logout-all',
        'password_email' => 'password/email',
        'password_update' => 'password/update',
        'token_refresh' => 'token/refresh',
    ],

    /*
     * Token expiry configuration.
     */

    /*
     * All tokens are refreshed upon use by default. If you wish to disable this behaviour, you can set this
     * value to false. Note that you will have to manually refresh the tokens using the token refresh route.
     */
    'auto_refresh_tokens' => true,

    /*
     * How long it takes for a token to expire. Tokens get refreshed everytime they are used. Hence, a
     * lifetime of 43200 minutes will cause the token to expire if it hasn't been used for 30 days.
     * A value of 0 will disable expiration completely.
     */
    'token_lifetime' => env('TOKEN_LIFETIME', 43200),

    /*
     * Here you can modify the properties of the authentication guard. This name corrosponds
     * to the guards array in config/auth.php. Please not that if you change this value,
     * the default api middleware as declared in App\Http\Kernel will not be used.
     */
    'guard_name' => 'api',
];
