<?php

namespace Codificar\PaymentGateways\Http\Controllers;

use App\Http\Controllers\Controller;

use Codificar\PaymentGateways\Http\Requests\CertificatesFormRequest;

use Storage;
use Response;
use Document;
use Log;
use View;

class JunoController extends Controller
{
    /**
     * Juno webview add card page 
     * @return View
     */
    public function addCardJuno()
    {

        //retorna view
        return View::make('gateways::juno')
            ->with([
                'juno_sandbox' => false,
                'public_token' => "80F17BE20F828204BD9C382DD784F5DBDA8E2838000D8E28A562E502FA7DC998"
            ]);
    }

     /**
     * @api{post}/libs/settings/save/gateways
     * Save payment default and confs
     * @return Json
     */

    public function saveCardJuno()
    {
        $userId = Input::get('user_id');

        $payment = new Payment ;
		$payment->user_id = $userId ;
		$return = ['error' => null] ;
		
		//uso da factory internamente
		$return = $payment->createCard($cardNumber, $cardExpirationMonth, $cardExpirationYear, $cardCvv, $cardHolder, $cardPassword);

		$return['payment'] = $payment ;
        
        $json = array(
            'success'=>false
        );
		return Response::json($json);
    }
}