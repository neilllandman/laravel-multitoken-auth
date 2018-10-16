<?php
$mappings = \Landman\MultiTokenAuth\Classes\TokenApp::config('route_mappings');

Route::group([], function () use ($mappings) {
    Route::group([], function () use ($mappings) {
        if (isset($mappings['login']))
            Route::post($mappings['login'], 'ApiAuthController@login');
        if (isset($mappings['register']))
            Route::post($mappings['register'], 'ApiAuthController@register');

        Route::group(['middleware' => 'auth:api'], function () use ($mappings) {
            if (isset($mappings['devices'])) {
                Route::get($mappings['devices'], 'ApiAuthController@devices');
                Route::post($mappings['devices'] . "/logout/{id}", 'ApiAuthController@devicesLogout');
            }
            if (isset($mappings['logout'])) {
                Route::post($mappings['logout'], 'ApiAuthController@logout');
            }
            if (isset($mappings['logout_all']))
                Route::post($mappings['logout_all'], 'ApiAuthController@logoutAll');
            if (isset($mappings['user']))
                Route::get($mappings['user'], 'ApiAuthController@user');
            if (isset($mappings['password_update']))
                Route::post($mappings['password_update'], 'ApiAuthController@updatePassword');
            if (isset($mappings['token_refresh']))
                Route::post($mappings['token_refresh'], 'ApiAuthController@refreshToken');
        });
        if (isset($mappings['password_email']))
            Route::post($mappings['password_email'], 'ApiAuthController@sendResetLinkEmail');
    });
});
