<?php

Route::group(['prefix' => 'auth', 'namespace' => 'Landman\\MultiTokenAuth'], function () {
    Route::post('{login}', 'ApiAuthController@login')->where('login', '(login|authenticate)');
    Route::group(['prefix' => 'token'], function () {
        Route::post('refresh', 'ApiAuthController@refreshToken');
    });

//    Route::group(['prefix' => 'password'], function () {
//        Route::post('email', 'ForgotPasswordController@getResetToken');
//        Route::post('reset', 'ResetPasswordController@reset');
//        Route::post('update', 'ApiAuthController@updatePassword');
//    });
});
