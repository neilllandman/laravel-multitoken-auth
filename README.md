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
<br><code>composer require neilllandman/laravel-multitoken-auth:"^1.0"</code>

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

    class User extends Model {
        use \Landman\MultiTokenAuth\Traits\HasMultipleApiTokens;
        .
        .
    }

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
<tr><td>POST</td><td>/api/auth/register</td><td>No</td>
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
    
    class User extends Model {
        .
        .
        .
        public function canAccessApi(): bool
        {
            return $this->hasRole(['consumer', 'vendor']);
        }
    }


<h2>Configuration</h2>

Please see comments in https://gitlab.com/neilllandman/laravel-multitoken-auth/blob/master/config/multipletokens.php for more information. 

Publish config/multipletokens.php.
<br><code>php artisan vendor:publish</code>

<h4>Available configuration options</h4>
<table>
<thead><tr><th>Name</th><th>Description</th><th>Default</th><tr></thead>
<tbody>
<tr>
<td>model</td>
<td>The Eloquent model class to use.</td>
<td>App\User</td>
</tr>
<tr>
<td>username</td>
<td>Eloquent model username column. This column will be used for authentication on login.</td>
<td>email</td>
</tr>
<tr>
<td>tables</td>
<td>The names of the tables created when running the migrations.</td>
<td>

    [
        'clients' => 'api_clients',
        'tokens' => 'api_tokens'
    ]
</td>
</tr>
<tr>
<td>login.validation</td>
<td>Validation rules to use upon login. Note that client_id is always validated.</td>
<td>

    [
        'email' => 'required|email|string',
        'password' => 'required|string',
    ]
</td>
</tr>
<tr>
<td>register.validation</td>
<td>The fields that will be passed to the create method of the given Eloquent model. Note that if the password field is present it will be encrypted using bcrypt().</td>
<td>

    ['name','email','password'],
</td>
</tr>

<tr>
<td>register.validation</td>
<td>Validation rules to use upon registration. If the 'fields' array above is not given, the keys for this array will be used.</td>
<td>
    
    [
        'name' => 'required|string|min:2',
        'email' => 'required|email|string|unique:users',
        'password' => 'required|string|min:12|confirmed',
    ]
</td>
</tr>

<tr>
<td>routes</td>
<td>Route mappings. If you would like to change the default route paths, you can do that here.</td>
<td>
    
    [
        'middleware' => ['api'],
        'prefix' => 'api',
        'mappings' => [
            'login' => '/auth/login',
            'register' => '/auth/register',
            'user' => '/auth/user',
            'logout' => '/auth/logout',
            'logout-all' => '/auth/logout-all',
            'token-refresh' => 'token/refresh',
        ],
    ]
</td>
</tr>
<tr>
<td>model-is-listening</td>
<td>Specifies whether the model event functions should be fired. (Refer to Events)</td>
<td>false</td>
</tr>
</table>

<h2>Events</h2>
To hook into the login and register functions, you should publish the configuration files and set the 'model-is-listening' option to true and add the <code>ListensOnApiEvents</code> trait to your user model and override the necessary methods.

    class User extends Model {
        use \Landman\MultiTokenAuth\Traits\HasMultipleApiTokens;
        use \Landman\MultiTokenAuth\Traits\ListensOnApiEvents;
        .
        .
    }

This trait exposes the following methods, all of which receives the current request as the only parameter and must return the user.
<table>
<thead>
<tr><th>Method Name</th><th>Description</th><tr>
</thead>
<tbody>
<tr><td><code>beforeApiRegistered</code></td><td>Runs before the model is saved. (You can save the user inside the method as well).</td><tr>
<tr><td><code>afterApiRegistered</code></td><td>Runs after successful registeration.</td><tr>
<tr><td><code>afterApiLogin</code></td><td>Runs after successful login.</td><tr>
<tr><td><code>afterApiLogout</code></td><td>Runs after successful logout.</td><tr>
</tbody>
</table>

Example:

    class User extends Model {
        .
        .
        .
        
        public function afterApiLogin($request)
        {
            $this->update(['last_login_at' => now()]);
            return $this;
        }
        
        public function afterApiRegistered($request)
        {
            $this->assignRole($request->input('role'));
            return $this;
        }
    }
 
