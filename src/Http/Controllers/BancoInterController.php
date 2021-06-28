<?php

namespace Codificar\PaymentGateways\Http\Controllers;

use App\Http\Controllers\Controller;

use Storage;
use Response;
use Document;
use Log;

use Codificar\PaymentGateways\Libs\BancoInterApi;

class BancoInterController extends Controller
{

    public function showBilletPdf($pdfName){     

        $filePath = getcwd() .'/..'. Storage::url('app/storage/billets/'.$pdfName.'.pdf');

        return response()->file($filePath);
    }
}
