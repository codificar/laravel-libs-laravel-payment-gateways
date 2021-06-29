<?php

namespace Codificar\PaymentGateways\Http\Controllers;

use App\Http\Controllers\Controller;

use Codificar\PaymentGateways\Http\Requests\CertificatesFormRequest;

use Storage;
use Response;
use Document;
use Log;

use Codificar\PaymentGateways\Libs\BancoInterApi;

class BancoInterController extends Controller
{

    public function showBilletPdf($pdfName){     

        $filePath = getcwd() .'/..'. Storage::url('app/billets/'.$pdfName.'.pdf');

        return response()->file($filePath);
    }
    
    public function saveCertificates(CertificatesFormRequest $request){
        if(isset($request->crt))
            $crt = $request->crt->storeAs('certificates', "BancoInterCertificate.pem");

        if(isset($request->key))
            $key = $request->key->storeAs('certificates', "BancoInterKey.pem");
    }
}
