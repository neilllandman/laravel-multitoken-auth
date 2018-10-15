<?php
/**
 * Created by PhpStorm.
 * User: neill
 * Date: 2018/10/13
 * Time: 10:21 AM
 */

namespace Landman\MultiTokenAuth\Traits;


use Landman\MultiTokenAuth\Classes\TokenApp;

/**
 * Trait ValidatesClientId
 * @package Landman\MultiTokenAuth\Traits
 */
trait ValidatesClientId
{

    /**
     * @return bool
     */
    private function requestHasValidClientId()
    {
        return TokenApp::validateClientId(request()->input('client_id'));
    }

    /**
     * @return bool
     */
    private function requestHasInvalidClientId()
    {
        return $this->requestHasValidClientId() === false;
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    private function invalidClientIdResponse()
    {
        return response()->json(['message' => 'Invalid client id specified.'], 401);
    }
}
