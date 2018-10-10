<?php

return [

    'tables' => [
        'api_clients' => 'api_clients',
        'api_tokens' => 'api_tokens'
    ],

    'username' => 'email',

    'login-validation' => [
        'client_id' => 'required|string',
        'email' => 'required|email|string',
        'password' => 'required|string',
    ],

    'use-client-id' => true,
    'client-id' => env('MULTI_TOKEN_CLIENT_ID', null),
];
