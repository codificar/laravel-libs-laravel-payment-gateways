<?php

namespace Codificar\PaymentGateways\Http\Controllers;

use App\Http\Controllers\Controller;

use Storage;
use Response;

class BancardController extends Controller
{

    public function showBilletPdf($pdfName){
        return view('bancoInter.billet_pdf')->with(array('pdfName' => $pdfName));
    }

    public function getBilletPdf($pdfName){
        $pdf = Document::findOrFail($pdfName);

        $filePath = $pdf->file_path;
    
        // file not found
        if( ! Storage::exists($filePath) ) {
          abort(404);
        }
    
        $pdfContent = Storage::get($filePath);
    
        // for pdf, it will be 'application/pdf'
        $type       = Storage::mimeType($filePath);
        $fileName   = Storage::name($filePath);
    
        return Response::make($pdfContent, 200, [
          'Content-Type'        => $type,
          'Content-Disposition' => 'inline; filename="'.$fileName.'"'
        ]);
    }
}
