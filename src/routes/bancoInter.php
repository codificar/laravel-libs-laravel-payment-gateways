<?php
Route::group(array('namespace' => 'Codificar\PaymentGateways\Http\Controllers'), function () {
    Route::group(['prefix' => 'libs/gateways/banco_inter'], function () {
        Route::group(['middleware' => ['auth.admin_api', 'cors']], function () {
            Route::post('/save_certificates', array('as' => 'save_certificates', 'uses' => 'BancoInterController@saveCertificates'));
        });
        Route::get('/billet/{pdfName}', array('as' => 'billetPdf', 'uses' => 'BancoInterController@showBilletPdf'));
    });
});