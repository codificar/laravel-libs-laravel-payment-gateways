<?php

// Rotas do painel
Route::group(array('namespace' => 'Codificar\PaymentGateways\Http\Controllers'), function () {

    // (View painel admin)
    Route::group(['prefix' => 'admin/settings', 'middleware' => 'auth.admin'], function () {
        Route::get('/gateways', array('as' => 'webAdminSettingsGateways', 'uses' => 'GatewaysController@getSettings'));
    });
});

// Rotas apis
Route::group(array('namespace' => 'Codificar\PaymentGateways\Http\Controllers'), function () {

    Route::group(['prefix' => 'libs/settings'], function () {

        Route::post('/save/gateways', array('as' => 'webAdminSaveSettingsGateways', 'uses' => 'GatewaysController@saveSettings'));
        Route::post('/save/billet_invoice', array('as' => 'webAdminSaveBilletIvoice', 'uses' => 'GatewaysController@saveBilletInvoiceSettings'));
    });
});

/**
 * Rota para permitir utilizar arquivos de traducao do laravel (dessa lib) no vue js
 */
Route::get('/libs/gateways/lang.trans/{file}', function () {
    $fileNames = explode(',', Request::segment(4));
    $lang = config('app.locale');
    $files = array();
    foreach ($fileNames as $fileName) {
        array_push($files, __DIR__ . '/../resources/lang/' . $lang . '/' . $fileName . '.php');
    }
    $strings = [];
    foreach ($files as $file) {
        $name = basename($file, '.php');
        $strings[$name] = require $file;
    }

    header('Content-Type: text/javascript');
    return ('window.lang = ' . json_encode($strings) . ';');
    exit();
})->name('assets.lang');
