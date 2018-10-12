<?php

namespace Landman\MultiTokenAuth\Traits;


use Landman\MultiTokenAuth\Models\ApiToken;

/**
 * Trait HasMultipleApiTokens
 * @package Landman\MultiTokenAuth\Traits
 */
trait HasMultipleApiTokens
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function apiTokens()
    {
        return $this->hasMany(ApiToken::class);
    }

    /**
     * @return int
     */
    public function invalidateAllTokens()
    {
        $count = $this->apiTokens()->count();
        $this->apiTokens()->each(function (ApiToken $apiToken) {
            $apiToken->invalidate();
        });
        return $count;
    }

    /**
     * @return false|\Illuminate\Database\Eloquent\Model
     */
    public function issueToken()
    {
        return $this->apiTokens()->save(new ApiToken());
    }

    /**
     * @return bool
     */
    public function canAccessApi(): bool
    {
        return true;
    }

    /**
     * @param $request
     */
    public function onApiRegistered($request)
    {

    }

    /**
     * @return $this
     */
    public function toApiFormat()
    {
        return $this;
    }
}
