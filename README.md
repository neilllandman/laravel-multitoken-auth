# Multiple Tokens
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

Run migrations:
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

<h2>Managing Client IDs</h2>

You can manage clients by using the artisan commands


Create API client ID: 
<br><code>php artisan landman:tokens:make-client {name}</code>

Note that the names must be unique.

List Client IDs: 
<br><code>php artisan landman:tokens:list-clients</code>
<br>Sample output: 
    
    +---------+--------------------------------------+
    | Name    | Api Client ID                        |
    +---------+--------------------------------------+
    | Android | 577e1bbb-dfd7-0000-8b28-e54c536a9738 |
    | iOS     | 577e1bbf-ef19-0000-bb59-97193ffe088c |
    +---------+--------------------------------------+


Delete a Client: 
<br><code>php artisan landman:tokens:delete-client {name}</code>

Refresh a Client (this will reset the 'Api Client ID' value): 
<br><code>php artisan landman:tokens:refresh-client {name}</code>


<h2>Routes</h2>
Some routes are provided by default. Authenticated routes require an Authorization header with a valid token.
<table>
<thead>
<tr><th>Method</th><th>URI</th><th>Authentication Required</th></tr>
</thead>
<tbody>
<tr><td>POST</td><td>/api/auth/login</td><td>No</td>
<tr><td>POST</td><td>/api/auth/logout</td><td>Yes</td></tr>
<tr><td>POST</td><td>/api/auth/logout-all</td><td>Yes</td></tr>
<tr><td>GET|HEAD</td><td>/api/auth/user</td><td>Yes</td></tr>
</tbody>
</table>

<h2>Usage</h2>

Login
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
Authorization Header: <code>Authorization: Bearer EYnMURaZ2Q0wWqv4JKYJZtWShqEu6LDk17yKNZwcOuoDaRIsGJXUsXcfBqAV</code>

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

To manually issue an ApiToken to a user, the <code>$user->issueToken()</code> method can be used. This method returns an instance of <code>\Landman\MultiTokenAuth\Models\ApiToken</code>.

To further validate if a user can access the API, you can override the <code>canAccessApi()</code> method available from the <code>HasMultipleApiTokens</code> trait.

For example, if you would like to restrict access to your API to allow only users with certain roles:
    
    public function canAccessApi(): bool
    {
        return $this->hasRole(['consumer', 'vendor']);
    }


<h2>Configuration</h2>


Publish config/multipletokens.php.
<br><code>php artisan vendor:publish</code>

<h4>Available configuration options</h4>
<table>
<thead><tr><th>Name</th><th>Description</th><th>Default</th><tr></thead>
<tbody>
<tr>
<td>model</td>
<td>The class of the eloquent user model to use.</td>
<td>App\User</td>
</tr>
<tr>
<td>username</td>
<td>The username column of the model.</td>
<td>email</td>
</tr>
<tr>
<td>login-validation</td>
<td>The validation array to use when logging in.</td>
<td>*See validation</td>
</tr>

<td>tables</td>
<td>The names of the tables created when running the migrations.</td>
<td>*See migrations</td>
</tr>
</table>
