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
    'tables' => [
        'clients' => 'api_clients',
        'tokens' => 'api_tokens'
    ],

    /*
     * Login configurations.
     */
    'login' => [
        /*
         * Validation rules to use upon login. Note that client_id is always validated.
         */
        'validation' => [
            'email' => 'required|email|string',
            'password' => 'required|string',
        ]
    ],

    /*
     * Register configuration.
     */
    'register' => [
        /*
         * The fields that will be passed to the create method of the given Eloquent model. Note
         * that if the password field is present it will be encrypted using bcrypt().
         * The keys in the validation array (see below) is used by default, but you
         * can uncomment this line if need be.
         */

//        'fields' => ['name', 'email', 'password'],

        /*
         * You can also specify the fields passed to be the fillable array declared on your User model.
         */
        'usefillable' => false,

        /*
         * Validation rules to use upon registration. If the 'fields' array above
         * is not given, the keys for this array will be used.
         */
        'validation' => [
            'name' => 'required|string|min:2',
            'email' => 'required|email|string|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ],
    ],

    /*
     * Route mappings. If you would like to change the default route paths, you can do that here.
     */
    'routes' => [

        /* Middleware to apply to all routes. By default, we're using Laravel's api group. */
        'middleware' => ['api'],

        /* Prefix for all routes */
        'prefix' => 'api',

        /* Mappings */
        'mappings' => [
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
    ],

    /*
     * Events
     */
    'model-is-listening' => false,
];
