<?php

namespace Landman\MultiTokenAuth\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Created by PhpStorm.
 * User: neilllandman
 * Date: 2018/03/07
 * Time: 08:20
 */
class TokensUserProvider implements UserProvider
{
    /**
     * The Eloquent user model.
     *
     * @var string
     */
    protected $model;

    /**
     * @var string
     */
    protected $relationName;

    /**
     * Create a new database user provider.
     *
     * @param  \Illuminate\Contracts\Hashing\Hasher $hasher
     * @param  string $model
     * @return void
     */
    public function __construct($model)
    {
        $this->model = $model;
        $this->relationName = 'apiTokens';
    }

    /**
     * Retrieve a user by their unique identifier.
     *
     * @param  mixed $identifier
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveById($identifier)
    {
        $model = $this->createModel();

        return $model->newQuery()
            ->where($model->getAuthIdentifierName(), $identifier)
            ->first();
    }

    /**
     * Retrieve a user by their unique identifier and "remember me" token.
     *
     * @param  mixed $identifier
     * @param  string $token
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByToken($identifier, $token)
    {
    }

    /**
     * Update the "remember me" token for the given user in storage.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable $user
     * @param  string $token
     * @return void
     */
    public function updateRememberToken(Authenticatable $user, $token)
    {
    }

    /**
     * Retrieve a user by the given credentials.
     *
     * @param  array $credentials
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials)
    {
        if (!empty($credentials['api_token'])) {
            $query = $this->createModel()->newQuery();
            $query->whereHas("{$this->relationName}", function ($q) use ($credentials) {
                $q->where('token', $credentials['api_token']);
            });
            return $query->first();
        } else {
            if (empty($credentials) ||
                (count($credentials) === 1 &&
                    array_key_exists('password', $credentials))
            ) {
                return;
            }

            // First we will add each credential element to the query as a where clause.
            // Then we can execute the query and, if we found a user, return it in a
            // Eloquent User "model" that will be utilized by the Guard instances.
            $query = $this->createModel()->newQuery();

            foreach ($credentials as $key => $value) {
                if (!Str::contains($key, 'password')) {
                    $query->where($key, $value);
                }
            }

            return $query->first();
        }
        return;


        // First we will add each credential element to the query as a where clause.
        // Then we can execute the query and, if we found a user, return it in a
        // Eloquent User "model" that will be utilized by the Guard instances.
    }

    /**
     * Validate a user against the given credentials.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  array  $credentials
     * @return bool
     */
    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        $plain = $credentials['password'];

        return $user->canAccessApi() && Hash::check($plain, $user->getAuthPassword());
    }

    /**
     * Create a new instance of the model.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function createModel()
    {
        $class = '\\' . ltrim(get_class($this->model), '\\');
        return new $class;
    }

    /**
     * Gets the name of the Eloquent user model.
     *
     * @return string
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Sets the name of the Eloquent user model.
     *
     * @param  string $model
     * @return $this
     */
    public function setModel($model)
    {
        $this->model = $model;

        return $this;
    }
}
