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
     * @return false|\Illuminate\Database\Eloquent\Model
     */
    public function issueToken()
    {
        return $this->apiTokens()->save(new ApiToken());
    }
}
