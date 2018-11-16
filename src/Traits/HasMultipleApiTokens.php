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
        return $this->hasMany(ApiToken::class, 'user_id');
    }

    /**
     * @param ApiToken|null $except
     * @return bool
     */
    public function invalidateAllTokens(ApiToken $except = null): bool
    {
        if ($except) {
            $apiTokens = $this->apiTokens()->where('id', '!=', $except->id)->get();
        } else {
            $apiTokens = $this->apiTokens()->get();
        }
        $apiTokens->each(function (ApiToken $apiToken) {
            $apiToken->invalidate();
        });
        return $apiTokens->count();
    }

    /**
     * @return false|ApiToken
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
     * @return $this
     */
    public function toApiFormat()
    {
        return $this;
    }
}
