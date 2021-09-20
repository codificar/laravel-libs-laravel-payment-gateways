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
        $creditCardHash = Input::get('credit_card_hash');
        $cardId = Input::get('card_id');
        
        //check if cardId is from user (for security)
        $payment = Payment::find($cardId);
        if($payment->user_id != $userId) {
            return Response::json(array('success' => false));
        } else {
            $cardToken = JunoLib::createCardToken($creditCardHash);
            if(!$cardToken) {
                //se der erro na tokenizacao, entao deleta o cartao do banco de dados
                $payment->delete();
                return Response::json(array('success' => false));
            }
            else {
                $payment->card_token = $cardToken;
                $payment->customer_id = $cardToken;
                $payment->save();

                return Response::json(array('success' => true));
            }
        }
    }
}