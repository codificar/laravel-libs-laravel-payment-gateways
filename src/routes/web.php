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

    Route::group(['prefix' => 'libs/settings', 'middleware' => 'auth.admin'], function () {
        Route::post('/save/gateways', array('as' => 'webAdminSaveSettingsGateways', 'uses' => 'GatewaysController@saveSettings'));
    });
});

// Rotas publicas
Route::group(array('namespace' => 'Codificar\PaymentGateways\Http\Controllers'), function () {

    Route::group(['prefix' => 'libs/gateways'], function () {

        Route::get('/nomenclatures', array('as' => 'webNomenclatures', 'uses' => 'GatewaysController@getNomenclatures'));
    });
});

/**
 * Rota para permitir utilizar arquivos de traducao do laravel (dessa lib) no vue js
 */
Route::get('/libs/gateways/lang.trans/{file}', function () {

    app('debugbar')->disable();

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

    return response('window.lang = ' . json_encode($strings) . ';')
            ->header('Content-Type', 'text/javascript');
            
})->name('assets.lang');
