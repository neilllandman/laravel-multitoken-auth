<?php

namespace Landman\MultiTokenAuth\Traits;


/**
 * Trait HasMultipleApiTokens
 * @package Landman\MultiTokenAuth\Traits
 */
trait HasMultipleApiTokens
{
    /**
     * @return bool
     */
    public function canAccessApi()
    {
        return true;
    }
}
