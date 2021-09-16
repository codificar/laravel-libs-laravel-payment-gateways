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
        $last_four = Input::get('last_four');
        $card_type = Input::get('card_type');
        $creditCardHash = Input::get('credit_card_hash');

        $gateway = PaymentFactory::createGateway();
		$cardToken = $gateway->createCardToken($creditCardHash);
        if(!$cardToken) {
            return Response::json(array('success' => false));
        }
        else {
            $payment = new Payment;
            $payment->user_id = $userId;
            $payment->gateway = "juno";
            $payment->last_four = $last_four;
            $payment->card_type = $card_type;
            $payment->is_active = 1;
            $payment->card_token = $cardToken;
            $payment->customer_id = $cardToken;
		    $payment->save();

            return Response::json(array('success' => true));
        }
    }
}