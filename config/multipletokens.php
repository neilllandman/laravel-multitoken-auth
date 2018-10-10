<?php

return [

    /*
     * Eloquent model class and username column.
     */
    'model' => 'App\\User',
    'username' => 'email',

    /*
     * Validation rules to use upon login.
     */
    'login-validation' => [
        'client_id' => 'required|string',
        'email' => 'required|email|string',
        'password' => 'required|string',
    ],

    /*
     * Table names for migrations.
     */
    'tables' => [
        'api_clients' => 'api_clients',
        'api_tokens' => 'api_tokens'
    ],
];
