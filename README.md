# Multiple Tokens
(Full documentation to follow some day)


<h2>Installation</h2>

Add the following at the bottom of your composer.json to access the repo

    "repositories": [
        {
            "type": "vcs",
            "url": "https://gitlab.com/neilllandman/laravel-multitoken-auth.git"
        }
    ]


then run 
<br><code>composer require neilllandman/laravel-multitoken-auth</code>

Publish config/multipletokens.php file if you want to change the table names and default login validation.
<br><code>php artisan vendor:publish</code>

Run migrations: (edit config to change table names if you want)
<br><code>php artisan migrate</code>

Edit config/auth.php:

    'guards' => [
        ...,
        'api' => [
            'driver' => 'multi-tokens',
            'provider' => 'token-users',
        ],
    ],


Add trait to User model:
<br><code>use \Landman\MultiTokenAuth\Traits\HasMultipleApiTokens;</code>

<h2>Commands</h2>

Create API client ids: 
<br><code>php artisan landman:tokens:make-client ClientName</code>

List Client Ids: 
<br><code>php artisan landman:tokens:list-clients</code>

<h2>Usage</h2>

Login via route:
<br><code>/api/auth/login</code>

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
<br>
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




<h2>Models</h2>
The user's api tokens can be retrieved via the <code>$user->apiTokens()</code> relationship.

To manually issue an ApiToken to a user, the <code>$user->issueToken()</code> method can be used. This method returns an instance of \Landman\MultiTokenAuth\Models\ApiToken

To further validate if a user can access the API, you can override the <code>canAccessApi()</code> method available from the <code>HasMultipleApiTokens</code> trait.

For example, if you would like to restrict access to your API to allow only users with certain roles:
    
    public function canAccessApi(): bool
    {
        return $this->hasRole(['consumer', 'vendor']);
    }
