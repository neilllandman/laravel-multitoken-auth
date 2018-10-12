<?php
$mappings = \Illuminate\Support\Facades\Config::get('multipletokens.route_mappings');

Route::group([], function () use ($mappings) {

    Route::group([], function () use ($mappings) {
        Route::post($mappings['login'], 'ApiAuthController@login');

        Route::post($mappings['register'], 'ApiAuthController@register');

        Route::group(['middleware' => 'auth:api'], function () use ($mappings) {

            Route::get($mappings['devices'], 'ApiAuthController@devices');

            Route::post($mappings['logout'], 'ApiAuthController@logout');
            Route::post($mappings['logout-all'], 'ApiAuthController@logoutAll');

            Route::get($mappings['user'], 'ApiAuthController@user');
            Route::post($mappings['password-update'], 'ApiAuthController@updatePassword');
        });

        Route::post($mappings['password-email'], 'ApiAuthController@sendResetLinkEmail');
    });
});
