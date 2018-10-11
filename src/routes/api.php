<?php
$config = \Illuminate\Support\Facades\Config::get('multipletokens.routes');
$mappings = $config['mappings'];

Route::group(['prefix' => $config['prefix']], function () use($mappings) {

    Route::group(['namespace' => 'Landman\\MultiTokenAuth\\Http\\Controllers'], function () use($mappings) {
        Route::post($mappings['login'], 'ApiAuthController@login');

        Route::post($mappings['register'], 'ApiAuthController@register');

        Route::group(['middleware' => 'auth:api'], function () use($mappings) {

            Route::post($mappings['logout'], 'ApiAuthController@logout');
            Route::post($mappings['logout-all'], 'ApiAuthController@logoutAll');
            Route::post($mappings['token-refresh'], 'ApiAuthController@refreshToken');

            Route::get($mappings['user'], 'ApiAuthController@user');
        });

//    Route::group(['prefix' => 'password'], function () {
//        Route::post('email', 'ForgotPasswordController@getResetToken');
//        Route::post('reset', 'ResetPasswordController@reset');
//        Route::post('update', 'ApiAuthController@updatePassword');
//    });
    });
});
