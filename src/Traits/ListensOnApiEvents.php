<?php

namespace Landman\MultiTokenAuth\Traits;

use Illuminate\Http\Request;

use Landman\MultiTokenAuth\Models\ApiToken;

/**
 * Trait HasMultipleApiTokens
 * @package Landman\MultiTokenAuth\Traits
 */
trait ListensOnApiEvents
{
    /**
     * @param $request
     * @return $this
     */
    public function beforeApiRegistered($request)
    {
        return $this;
    }

    /**
     * @param $request
     */
    public function afterApiRegistered($request)
    {
        return $this;
    }

    /**
     * @param $request
     */
    public function afterApiLogin($request)
    {
        return $this;
    }

    /**
     * @param $request
     */
    public function afterApiLogout($request)
    {
        return $this;
    }
}
