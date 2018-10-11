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
    'login-validation' => [
        'username' => 'required|email|string',
        'password' => 'required|string',
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
