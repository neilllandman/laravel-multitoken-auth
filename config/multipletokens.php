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
     * Validation rules to use upon login. Note that client_id is always validated.
     */
    'login' => [
        'validation' => [
            'email' => 'required|email|string',
            'password' => 'required|string',
        ]
    ],


    'register' => [
        /* The fields that will be passed to the create method of the given Eloquent model. Note
         * that if the password field is present it will be encrypted using bcrypt().
         */
        'fields' => ['name', 'email', 'password'],

        /*
         * Validation rules to use upon registration. If the 'fields' array above
         * is not given, the keys for this array will be used.
         */
        'validation' => [
            'name' => 'required|string|min:2',
            'email' => 'required|email|string|unique:users',
            'password' => 'required|string|min:12|confirmed',
        ],
    ],

    /*
     * Route mappings. If you would like to change the default route paths, you can do that here.
     */
    'routes' => [
        'prefix' => 'api',
        'mappings' => [
            'login' => '/auth/login',
            'register' => '/auth/register',
            'user' => '/auth/user',
            'logout' => '/auth/logout',
            'logout-all' => '/auth/logout-all',
            'token-refresh' => 'token/refresh',
        ],
    ]
];
