<?php

return [

    /*
     * Eloquent model class and username column.
     */
    'model' => 'App\\User',
    'username' => 'email',

    /*
     * Table names used for migrations.
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
        /* these fields will be passed to the create method of the given
         * Eloquent model. Note that if the password field is present it will be
         * encrypted using bcrypt().
         */
        'fields' => ['name', 'email', 'password'],

        /*
         * Validation rules to use upon registration. If the 'fields' array above
         * is not given, the keys for this array will be used
         */
        'validation' => [
            'name' => 'required|string|min:2',
            'email' => 'required|email|string|unique:users',
            'password' => 'required|string|min:12|confirmed',
        ],
    ],

    /*
     * Enabled routes. (Future)
     */
//    'routes' => [
//        'login',
//        'register',
//        'logout',
//        'logout-all',
//    ]
];
