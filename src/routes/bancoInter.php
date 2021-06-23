<?php
    Route::get('/billet/{pdfName}', array('as' => 'billetPdf', 'uses' => 'BancoInterController@showBilletPdf'));
    Route::get('/billet/get/{pdfName}', array('as' => 'getbilletPdf', 'uses' => 'BancoInterController@getBilletPdf'));