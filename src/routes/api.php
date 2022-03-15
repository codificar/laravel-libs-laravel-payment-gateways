<?php

// Rotas apis
Route::group(array('namespace' => 'Codificar\PaymentGateways\Http\Controllers'), function () {

    Route::group(['prefix' => 'libs/settings', 'middleware' => 'auth.admin'], function () {
        Route::post('/save/gateways', array('as' => 'webAdminSaveSettingsGateways', 'uses' => 'GatewaysController@saveSettings'));
    });
});

// Rotas publicas
Route::group(array('namespace' => 'Codificar\PaymentGateways\Http\Controllers'), function () {

    Route::group(['prefix' => 'libs/gateways'], function () {

        Route::get('/nomenclatures', array('as' => 'webNomenclatures', 'uses' => 'GatewaysController@getNomenclatures'));
        Route::get('/payment_methods', array('uses' => 'PaymentMethodsController@getPaymentMethods'));
    });
});