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
     * @param ApiToken|null $except
     * @return bool
     */
    public function invalidateAllTokens(ApiToken $except = null): bool
    {
        $query = $this->apiTokens();
        if ($except) {
            $query = $query->where('id', '!=', $except->id);
        }
        $count = $query->count();
        $query->each(function (ApiToken $apiToken) {
            $apiToken->invalidate();
        });
        return $count > -1;
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
     * @return $this
     */
    public function toApiFormat()
    {
        return $this;
    }

    /**
     * @param ApiToken|null $token
     * @return bool
     * @throws \Exception
     */
    public function apiLogout(ApiToken $token = null)
    {
        if ($token) {
            return $token->invalidate();
        } else {
            return $this->invalidateAllTokens();
        }
    }
}
