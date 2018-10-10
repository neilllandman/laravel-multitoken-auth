<?php
Route::group(['prefix' => 'api'], function () {

    Route::group(['prefix' => 'auth', 'namespace' => 'Landman\\MultiTokenAuth\\Http\\Controllers'], function () {
        Route::post('login', 'ApiAuthController@login');
//        Route::group(['prefix' => 'token'], function () {
//            Route::post('refresh', 'ApiAuthController@refreshToken');
//        });

        Route::group(['middleware' => 'auth:api'], function () {
            Route::post('logout', 'ApiAuthController@logout');
            Route::post('logout-all', 'ApiAuthController@logoutAll');
            Route::get('/user', 'ApiAuthController@user');
        });

//    Route::group(['prefix' => 'password'], function () {
//        Route::post('email', 'ForgotPasswordController@getResetToken');
//        Route::post('reset', 'ResetPasswordController@reset');
//        Route::post('update', 'ApiAuthController@updatePassword');
//    });
    });
});
