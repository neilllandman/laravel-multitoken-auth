# Multiple Tokens

(Full documentation to follow some day)

Add the following at the bottom of your composer.json to access the repo


    "repositories": [
        {
            "type": "vcs",
            "url": "https://gitlab.com/neilllandman/laravel-multitoken-auth.git"
        }
    ]


then run 

<code>composer require neilllandman/laravel-multitoken-auth</code>

Run migrations:
<code>php artisan migrate</code>

Create API client ids: 

<code>php artisan landman:tokens:make-client ClientName</code>

List Client Ids: 

<code>php artisan landman:tokens:list-clients</code>


Edit config/auth.php:

    'guards' => [
        ...,
        'api' => [
            'driver' => 'multi-tokens',
            'provider' => 'token-users',
        ],
    ],


Add trait to User model:

<code>use \Landman\MultiTokenAuth\Traits\HasMultipleApiTokens;</code>

#Usage

Login via route:

<code>/api/auth/login</code>

with params: 


    {
        "client_id": "id created via php artisan",
        "email": "email",
        "password": "pw"
    }

Response example: 
    
     {
        "user": {
            "id": 1,
            "name": "Mrs. Jailyn Boehm",
            "email": "zemlak.royce@example.org",
            "email_verified_at": "2018-10-10 12:09:12",
            "created_at": "2018-10-10 12:09:12",
            "updated_at": "2018-10-10 12:09:12"
            },
        "token": "EYnMURaZ2Q0wWqv4JKYJZtWShqEu6LDk17yKNZwcOuoDaRIsGJXUsXcfBqAV"
     }
    

Call authenticated routes using returned token in bearer token authorization header

Example: <code>/api/auth/user</code>
Headers: <code>Authorization: Bearer EYnMURaZ2Q0wWqv4JKYJZtWShqEu6LDk17yKNZwcOuoDaRIsGJXUsXcfBqAV</code>

Response: 

     {
        "id": 1,
        "name": "Mrs. Jailyn Boehm",
        "email": "zemlak.royce@example.org",
        "email_verified_at": "2018-10-10 12:09:12",
        "created_at": "2018-10-10 12:09:12",
        "updated_at": "2018-10-10 12:09:12"
     }



