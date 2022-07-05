<?php

// Rotas apis
Route::group(array('namespace' => 'Codificar\PaymentGateways\Http\Controllers'), function () {

    Route::group(['prefix' => 'libs/settings', 'middleware' => 'auth.admin'], function () {
        Route::post('/save/gateways', array('as' => 'webAdminSaveSettingsGateways', 'uses' => 'GatewaysController@saveSettings'));
        Route::post('/retrieve/webhooks', array('as' => 'webAdminRetrieveWebhooksPix', 'uses' => 'GatewaysController@retrieveWebhooks'));
    });
});

// Rotas publicas
Route::group(array('namespace' => 'Codificar\PaymentGateways\Http\Controllers'), function () {

    Route::group(['prefix' => 'libs/gateways'], function () {

        Route::get('/nomenclatures', array('as' => 'webNomenclatures', 'uses' => 'GatewaysController@getNomenclatures'));
        Route::get('/payment_methods', array('uses' => 'PaymentMethodsController@getPaymentMethods'));
    });
});

Route::group(array('namespace' => 'Codificar\PaymentGateways\Http\Controllers'), function () {
    Route::group(['prefix' => 'libs/gateways/provider', 'middleware' => 'auth.provider_api'], function () {
        Route::get('/get_payments', 'PaymentMethodsController@getProviderPayments');
        Route::post('/set_payments', 'PaymentMethodsController@setProviderPayments');
    });
});