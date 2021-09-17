<?php

namespace Codificar\PaymentGateways\Http\Controllers;

use App\Http\Controllers\Controller;

use Codificar\PaymentGateways\Http\Requests\CertificatesFormRequest;

use Storage;
use Response;
use Document;
use Log;
use View;
use Input;

use Settings, PaymentFactory, Payment;
use Codificar\PaymentGateways\Libs\JunoLib;

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
                'juno_sandbox' => (int)Settings::findByKey("juno_sandbox"),
                'public_token' => Settings::findByKey("juno_public_token")
            ]);
    }

     /**
     * @api{post}/libs/settings/save/gateways
     * Save payment default and confs
     * @return Json
     */

    public function saveCardJuno()
    {
        $userId = Input::get('id');
        $last_four = Input::get('last_four');
        $card_type = Input::get('card_type');
        $creditCardHash = Input::get('credit_card_hash');

		$cardToken = JunoLib::createCardToken($creditCardHash);
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
            $payment->is_default = 1;
            $payment->card_token = $cardToken;
            $payment->customer_id = $cardToken;
		    $payment->save();

            return Response::json(array('success' => true));
        }
    }
}