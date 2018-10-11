<?php
$config = \Illuminate\Support\Facades\Config::get('multipletokens.routes');
$mappings = $config['mappings'];

Route::group([], function () use ($mappings) {

    Route::group([], function () use ($mappings) {
        Route::post($mappings['login'], 'ApiAuthController@login');

        Route::post($mappings['register'], 'ApiAuthController@register');

        Route::group(['middleware' => 'auth:api'], function () use ($mappings) {

            Route::get($mappings['devices'], 'ApiAuthController@devices');

            Route::post($mappings['logout'], 'ApiAuthController@logout');
            Route::post($mappings['logout-all'], 'ApiAuthController@logoutAll');
            Route::post($mappings['token-refresh'], 'ApiAuthController@refreshToken');

            Route::get($mappings['user'], 'ApiAuthController@user');
            Route::post($mappings['password-update'], 'ApiAuthController@updatePassword');
        });

        Route::post($mappings['password-email'], 'ForgotPasswordController@getResetToken');
        Route::post($mappings['password-reset'], 'ResetPasswordController@reset');
    });
});
