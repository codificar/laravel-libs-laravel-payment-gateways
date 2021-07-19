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
        $crt = '';
        $key ='';
        if(isset($request->crt) && isset($request->key))
        {
            $crt = $request->crt->storeAs('certificates', "BancoInterCertificate.pem");
            $key = $request->key->storeAs('certificates', "BancoInterKey.pem");
            
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false, 'message' => 'Both files are required']);

    }
}
