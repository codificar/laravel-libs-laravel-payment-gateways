<?php
Route::group(array('namespace' => 'Codificar\PaymentGateways\Http\Controllers'), function () {
    Route::group(['prefix' => 'libs/gateways', 'middleware' => 'auth.admin_api:api'], function (){
        Route::get('/billet/{pdfName}', array('as' => 'billetPdf', 'uses' => 'BancoInterController@showBilletPdf'));
    });
});