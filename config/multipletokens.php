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
     * Route mappings. If you would like to change the default route paths, you can do that here.
     */
    'route_middleware' => ['api'],

    /* Prefix for all routes */
    'route_prefix' => 'api',

    /* Mappings */
    'route_mappings' => [
        'login' => '/auth/login',
        'register' => '/auth/register',
        'user' => '/auth/user',
        'devices' => '/user/api-devices',
        'logout' => '/auth/logout',
        'logout-all' => '/auth/logout-all',
        'token-refresh' => 'token/refresh',
        'password-email' => 'password/email',
        'password-reset' => 'password/reset',
        'password-update' => 'password/update',
    ],

    'tokens_expire' => true,

    'token_lifetime' => 14400,

    /*
     * Events
     */
    'model_is_listening' => false,
];
