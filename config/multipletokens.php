<?php

return [

    /*
     * Eloquent model class and username column.
     */
    'model' => 'App\\User',
    'username' => 'email',

    /*
     * Validation rules to use upon login. Note that the 'username' field
     * will be replaced by the username supplied above.
     */
    'login' => [
        'validation' => [
            'username' => 'required|email|string',
            'password' => 'required|string',
        ]
    ],


    'register' => [
        /*
         * Validation rules to use upon registration. Note that the 'username' field
         * will NOT be replaced by the username supplied above.
         */
        'validation' => [
            'name' => 'required|string|min:2',
            'username' => 'required|email|string',
            'password' => 'required|string|min:6|confirmed',
        ],

        /*
         * Fields to pass from the request into the create method for th given
         * Eloquent model. Note, that if the password field is present it
         * will be encrypted using bcrypt().
         */
        'fields' => ['name', 'email', 'password'],

    ],


    /*
     * Table names used for migrations.
     */
    'tables' => [
        'clients' => 'api_clients',
        'tokens' => 'api_tokens'
    ],

    /*
     * Enabled routes. (Future)
     */
    'routes' => [
        'login',
        'logout',
        'logout-all',
        'register',
    ]
];
