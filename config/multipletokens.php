<?php

return [
    'tables' => [
        'api_clients' => 'api_clients',
        'api_tokens' => 'api_tokens'
    ],

    'login-validation' => [
        'client_id' => 'required|string',
        'email' => 'required|email|string',
        'password' => 'required|string',
    ],
];
