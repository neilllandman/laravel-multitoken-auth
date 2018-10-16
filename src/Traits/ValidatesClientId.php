<?php
/**
 * Created by PhpStorm.
 * User: neill
 * Date: 2018/10/13
 * Time: 10:21 AM
 */

namespace Landman\MultiTokenAuth\Traits;


use Illuminate\Auth\AuthenticationException;
use Landman\MultiTokenAuth\Classes\TokenApp;

/**
 * Trait ValidatesClientId
 * @package Landman\MultiTokenAuth\Traits
 */
trait ValidatesClientId
{
    /**
     * @return bool
     * @throws AuthenticationException
     */
    protected function validateClientId(): bool
    {
        if (TokenApp::validateClientId(request()->input('client_id')) === false) {
            throw new AuthenticationException('Invalid client id.');
        }
        return true;
    }
}
