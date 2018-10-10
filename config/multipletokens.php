<?php

return [
    'username' => 'email',

    'login-validation' => [
        'client_id' => 'required|string',
        'email' => 'required|email|string',
        'password' => 'required|string',
    ],

    'use-client-id' => true,
    'client-id' => env('MULTI_TOKEN_CLIENT_ID', null),

    'client-ids' => [
        env('MULTI_TOKEN_CLIENT_ID', null),
        env('MULTI_TOKEN_CLIENT_ID_2', null),
        env('MULTI_TOKEN_CLIENT_ID_3', null),
    ],
];
