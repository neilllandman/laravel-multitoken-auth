# Introduction
A laravel package that allows simple API authentication by using [client ids](#managing-client-ids) and authorization tokens and provides configurable predefined [routes](#routes). The package also allows
1. Multiple tokens issued to the same user for different devices.
2. Managing user devices.
3. Requesting password reset emails.
3. Managing client ids via the artisan command line interface.
4. [Token expiration](#token-expiration) and auto refresh on use.
5. [Event hooks](#events) for login, logout and register.
6. Configuring login validation rules.
7. Configuring registration validation rules and fields.

#### Please read this documentation before jumping into [installation](#installation).


# Installation

Add the following at the bottom of your composer.json to access the repo

```
"repositories": [
    {
        "type": "vcs",
        "url": "https://gitlab.com/neilllandman/laravel-multitoken-auth.git"
    }
]
```
then run 
`composer require neilllandman/laravel-multitoken-auth:"^1.0"`

Run migrations:
`php artisan migrate`

Set the api guard driver and user provider in config/auth.php:
```
'guards' => [
    ...,
    'api' => [
        'driver' => 'multi-tokens',
        'provider' => 'token-users',
    ],
],
```

Add trait to User model: `\Landman\MultiTokenAuth\Traits\HasMultipleApiTokens`
```
class User extends Model {
    use \Landman\MultiTokenAuth\Traits\HasMultipleApiTokens;
    .
    .
}
```

# Managing Client IDs
You can manage clients by using the artisan commands

#### Create API client ID 
`php artisan landman:tokens:make-client {name}`

Note that the names must be unique.

#### List Client IDs 
`php artisan landman:tokens:list-clients`

Sample output: 
```
+---------+--------------------------------------+
| Name    | Api Client ID                        |
+---------+--------------------------------------+
| Android | 577e1bbb-dfd7-0000-8b28-e54c536a9738 |
| iOS     | 577e1bbf-ef19-0000-bb59-97193ffe088c |
+---------+--------------------------------------+
```

### Delete a Client
`php artisan landman:tokens:delete-client {name}`

### Refresh a Client 
this will reset the 'Api Client ID' value

`php artisan landman:tokens:refresh-client {name}`


# Routes
Some routes are provided by default. Authenticated routes require an Authorization header with a valid token.

<table>
<thead>
<tr><th>Method</th><th>URI</th><th>Description</th><th>Authenticated</th><th>Params</th></tr>
</thead>
<tbody>
<tr>
<td>POST</td>
<td>/api/auth/login</td>
<td>Login a user.</td>
<td>No</td>
<td>See [Configuration](#configuration)</td>
</tr>
<tr>
<td>POST</td>
<td>/api/auth/register</td>
<td>Register a user.</td>
<td>No</td>
<td>See [Configuration](#configuration)</td>
</tr>
<tr>
<td>POST</td>
<td>/api/auth/logout</td>
<td>Logout a user and invalidated the current token.</td>
<td>Yes</td>
<td>None</td>
</tr>
<tr>
<td>POST</td>
<td>/api/auth/logout_all</td>
<td>Logout a user from all their devices and invalidates all tokens.</td>
<td>Yes</td>
<td>None</td>
</tr>
<tr>
<td>GET|HEAD</td>
<td>/api/auth/user</td>
<td>Get the current user's data.</td>
<td>Yes</td>
<td>None</td>
</tr>
<tr>
<td>GET|HEAD</td>
<td>/api/auth/user/api-devices</td>
<td>Get a list of all devices (<code>id</code>,<code>user_agent</code>,<code>name</code> and timestamps) that the user has logged in from. Device names default to 'Unknown' if not specified on login or register.</td>
<td>Yes</td>
<td>None</td>
</tr>
<tr>
<td>POST</td>
<td>/api/auth/user/api-devices/logout/{id}</td>
<td>Logout from a specific device.</td>
<td>Yes</td>
<td>None</td>
</tr>
<tr>
<td>POST</td>
<td>/api/password/email</td>
<td>Send a password reset link via email.</td>
<td>No</td>
<td>

    email
    client_id 
</td>
</tr>
<tr>
<td>POST</td>
<td>/api/password/update</td>
<td>Update a user's password. This invalidates all api tokens except the current one.</td>
<td>Yes</td>
<td>

    
    password
    password_confirmation
    current_password
        
</td>
</tr>
</tbody>
</table>

# Usage [WIP]

### Login
<br>`/api/auth/login`

with params: 

```
{
    "client_id": "Api Client ID created via php artisan",
    "email": "email",
    "password": "pw"
}
```

Response example: 
``` 
 {
    "user": {
        "id": 1,
        "name": "Mrs. Jailyn Boehm",
        "email": "zemlak.royce@example.org",
        "email_verified_at": "2018-10-10 12:09:12",
        "created_at": "2018-10-10 12:09:12",
        "updated_at": "2018-10-10 12:09:12"
        },
    "auth": {
        "token": "EYnMURaZ2Q0wWqv4JKYJZtWShqEu6LDk17yKNZwcOuoDaRIsGJXUsXcfBqAV"
        }
 }
```
    


### Calling Authenticated routes

Getting the current user by calling `/api/auth/user`:

Authorization Header: `Authorization: Bearer EYnMURaZ2Q0wWqv4JKYJZtWShqEu6LDk17yKNZwcOuoDaRIsGJXUsXcfBqAV`

Response: 

     {
        "id": 1,
        "name": "Mrs. Jailyn Boehm",
        "email": "zemlak.royce@example.org",
        "email_verified_at": "2018-10-10 12:09:12",
        "created_at": "2018-10-10 12:09:12",
        "updated_at": "2018-10-10 12:09:12"
     }


### Password reset emails

Password reset emails are sent using the `sendPasswordResetNotification` function defined on the User model.

`/api/password/email`

with params: 
```
{
    "client_id": "Api Client ID created via php artisan",
    "email": "email",
}
```

Response example: 
```
    
{
    "message": "We have e-mailed your password reset link!"
}
``` 



# Models

#### Working with API tokens
The user's api tokens can be retrieved via the `$user->apiTokens()` relationship.

You can invalidate all api tokens for a user by calling `$user->invalidateAllTokens()`.

To manually issue an ApiToken to a user, the `$user->issueToken()` method can be used. This method returns an instance of `\Landman\MultiTokenAuth\Models\ApiToken`.

#### Further validation

To further validate if a user can access the API, you can override the `canAccessApi()` method available from the `HasMultipleApiTokens` trait.


For example, if you would like to restrict access to your API to allow only users with certain roles:
```
class User extends Model {
    .
    .
    .
    public function canAccessApi(): bool
    {
        return $this->hasRole(['consumer', 'vendor']);
    }
}
```

#### Formatting user response structure
If you would like to edit the structure for the user model on API responses, you may override the `toApiFormat()` method available from the `HasMultipleApiTokens` trait.
This method must return either an instance of the user model or an array.

```
class User extends Model {
    .
    .
    .
    public function toApiFormat()
    {
        $this->makeHidden(['created_at', 'updated_at']);
        $this->append('role_names');
        return $this;
    }
}
```
By default, the model itself is returned.

# Token Expiration

By default all tokens expire after 30 days (43200 minutes). However, this timer is reset on the token everytime it is used for authentication thus it will only expire if it is not used for this time period. If you do not want tokens to expire then you can set the [`token_lifetime`](#configuration) option, or `TOKEN_LIFETIME` environment variable to 0.
    
# Configuration

Please see comments in https://gitlab.com/neilllandman/laravel-multitoken-auth/blob/master/config/multipletokens.php for more information. 

Publish config/multipletokens.php: `php artisan vendor:publish`

### Available options
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
<td>password_field</td>
<td>Eloquent model password column. This column will be used for authentication on login.</td>
<td>password</td>
</tr>
<tr>
<td>table_clients</td>
<td>The name of the clients table created when running the migrations.</td>
<td>api_clients</td>
</tr>
<tr>
<td>table_tokens</td>
<td>The name of the tokens table created when running the migrations.</td>
<td>api_tokens</td>
</tr>
<tr>
<td>login_validation</td>
<td>Validation rules to use upon login. Note that client_id is always validated.</td>
<td>

    [
        'email' => 'required|email|string',
        'password' => 'required|string',
        'device' => 'sometimes|string',
    ]
</td>
</tr>
<tr>
<td>register_fields</td>
<td>The fields that will be passed to the create method of the given Eloquent model. Note that if the password field is present it will be encrypted using bcrypt().</td>
<td>

    // ['name','email','password'],
</td>
</tr>

<tr>
<td>register_usefillable</td>
<td>Use the fillable array specified on the User model.</td>
<td>
false
</td>
</tr>

<tr>
<td>register_validation</td>
<td>Validation rules to use upon registration. If the 'fields' array above is not given, the keys for this array will be used.</td>
<td>
    
    [
        'name' => 'required|string|min:2',
        'email' => 'required|email|string|unique:users',
        'password' => 'required|string|min:12|confirmed',
        'device' => 'sometimes|string',
    ]
</td>
</tr>

<tr>
<td>send_verification_email</td>
<td>Whether or not to send an email verification mail using the `sendEmailVerificationNotification` to the user upon registration. Refer to https://laravel.com/docs/5.7/verification</td>
<td>false</td>
</tr>




<tr>
<td>route_middleware</td>
<td>Middleware applied to all routes.</td>
<td>
    
    ['api']
</td>
</tr>
<tr>
<td>route_prefix</td>
<td>Prefix for all routes.</td>
<td>
    
    'api'
</td>
</tr>

<tr>
<td>route_mappings</td>
<td>Route mappings. If you would like to change the default route paths, you can do that here.</td>
<td>
        
    [
        'login' => '/auth/login',
        'register' => '/auth/register',
        'user' => '/auth/user',
        'devices' => '/auth/user/api-devices',
        'logout' => '/auth/logout',
        'logout_all' => '/auth/logout-all',
        'password_email' => 'password/email',
        'password_update' => 'password/update',
    ]
</td>
</tr>
<tr>
<td>token_lifetime</td>
<td>Time it takes for a token to expire in minutes. Tokens get refreshed everytime they are used. Default is 43200 (30 days)</td>
<td>
    
    43200
</td>
</tr>

</table>

# Events

### Via the ListensOnApiEvents Trait
To hook into the login and register functions, add the <code>ListensOnApiEvents</code> trait to your user model and override the necessary methods.

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
<tr><td><code>afterApiLogin</code></td><td>Runs after successful login. This method also gets fired after <code>afterApiRegistered</code></td><tr>
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
 
### Via Event Listeners
If you would like to register your own listeners, you can attach them to the following events
 
<table>
<thead>
<tr><th>Event</th></tr>
</thead>
<tbody>
<tr><td>Landman\MultiTokenAuth\Events\ApiAuthenticated</td></tr>
<tr><td>Landman\MultiTokenAuth\Events\ApiAuthenticating</td></tr>
<tr><td>Landman\MultiTokenAuth\Events\ApiLogin</td></tr>
<tr><td>Landman\MultiTokenAuth\Events\ApiLogout</td></tr>
<tr><td>Landman\MultiTokenAuth\Events\ApiRegistered</td></tr>
</tbody>
</table>

Each of these events expose the following properties:

 
<table>
<thead>
<tr><th>Property</th><th>Description</th></tr>
</thead>
<tbody>
<tr><td>$guard</td><td>The authentication guard</td></tr>
<tr><td>$user</td><td>The user that is currently being authenticated or is authenticated.</td></tr>
<tr><td>$token</td><td>The ApiToken used for authentication. Note that the token will be null for the ApiAuthenticating event.</td></tr>
</tbody>
</table>

Example 
```
<?php

namespace App\Listeners;
use Landman\MultiTokenAuth\Events\ApiLogin;
class UpdateUserLastLogin
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  Landman\MultiTokenAuth\Events\ApiLogin  $event
     * @return void
     */
    public function handle(ApiLogin $event)
    {
        $guard = $event->guard;
        $user = $event->user;
        $token = $event->token;

        $user->update(['last_login_at' => now()]);
    }
}
```


The package also listens to the `\Illuminate\Auth\Events\PasswordReset` event to invalidate all api tokens when changing the user's password. If you are not using the default Laravel password reset routes, you will have to do this manually (see `invalidateAllTokens` under [Models](#models)).

# Helper functions

The `Landman\MultiTokenAuth\Classes\TokenApp` class provides some static functions that might be useful. 

(See https://gitlab.com/neilllandman/laravel-multitoken-auth/blob/master/src/Classes/TokenApp.php)


### Creating clients

`TokenApp::makeClient($name)`

### Validating a client id

`TokenApp::validateClientId(string $clientId)`

### Accessing the authentication guard.

The authentication guard can be accessed via the normal Laravel `Auth` facade as `Auth::guard('api')` or via the (recommended) 
`TokenApp::guard()` method.

# Manual Registration and Login

If you prefer or need to write your own controller methods for login and registration, comment out the relevant routes in the configuration file.
You can use the provided guard to log the user in, automatically create a token and return a consistent response.


```
public function login(Request $request){
    $this->validate($request, [
        'email' => 'required|email',
        'password' => 'required|string'
    ]);
    $user = User::where('email', $request->input('email'))->first();
    
    $guard = TokenApp::guard();
    if($user && $guard->attempt($request->only(['email', 'password'])){
        return $guard->authenticatedResponse();
        // or access the api token and user via $guard->token() and $guard->user() to build your own response.
    }else{
        throw new AuthenticationException();
    }
}

public function register(Request $request){
    $this->validate($request, [
        'email' => 'required|email',
        'password' => 'required|string',
        .
        .
        .
    ]);
    ...
    $user = User::create($userData); 
    $guard = TokenApp::guard();
    $guard->login($user); // We know the user exists so there is no need to validate credentials via the attempt method
    return $guard->authenticatedResponse();
}
```

The `authenticatedResponse` method accepts one boolean parameter indicating whether a fresh user instance should be 
retrieved from the database and returns and instance of `\Illuminate\Http\JsonResponse` with structure
```
 {
    "user": [as defined in $user->toApiFormat()],
    "auth": {
            "token": "EYnMURaZ2Q0wWqv4JKYJZtWShqEu6LDk17yKNZwcOuoDaRIsGJXUsXcfBqAV"
        }   
 } 
```
 
An `AuthenticationException` is thrown if the user is not authenticated via the `login` or `attempt` methods.


