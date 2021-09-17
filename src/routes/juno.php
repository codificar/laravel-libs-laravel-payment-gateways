<?php

Route::group(array('namespace' => 'Codificar\PaymentGateways\Http\Controllers'), function () {
    Route::group(['prefix' => 'libs/gateways/juno'], function () {
        // Rota publica, para cadastro de cartao de credito na webview
        Route::get('/add_card', array('as' => 'addCardJuno', 'uses' => 'JunoController@addCardJuno'));
    });

    // Provider routes
    Route::group(['prefix' => 'libs/gateways/juno/add_card', 'middleware' => 'auth.provider_api:api'], function () {
        Route::post('/provider', array('as' => 'saveCardJunoProvider', 'uses' => 'JunoController@saveCardJuno'));
    });

    // Provider routes
    Route::group(['prefix' => 'libs/gateways/juno/add_card', 'middleware' => 'auth.user_api:api'], function () {
        Route::post('/user', array('as' => 'saveCardJunoUser', 'uses' => 'JunoController@saveCardJuno'));
    });

    // Admin routes
    Route::group(['prefix' => 'libs/gateways/juno/add_card', 'middleware' => 'auth.admin'], function () {
        Route::post('/admin', array('as' => 'saveCardJunoAdmin', 'uses' => 'JunoController@saveCardJuno'));
    });
});

