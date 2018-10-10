<?php

namespace Landman\MultiTokenAuth\Traits;


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
}
