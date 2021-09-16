<?php

Route::group(array('namespace' => 'Codificar\PaymentGateways\Http\Controllers'), function () {
    Route::group(['prefix' => 'libs/gateways/juno'], function () {
        // Rota publica, para cadastro de cartao de credito na webview
        Route::get('/add_card_juno', array('as' => 'addCardJuno', 'uses' => 'JunoController@addCardJuno'));
        Route::post('/add_card_juno/save', array('as' => 'saveCardJuno', 'uses' => 'JunoController@saveCardJuno'));
    });
});