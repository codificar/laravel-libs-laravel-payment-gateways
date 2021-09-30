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
     * @api{post}/libs/gateways/juno/add_card/provider
     * @return Json
     */
    public function saveCardJunoProvider()
    {
        $providerId = Input::get('id');
        $creditCardHash = Input::get('credit_card_hash');
        $cardId = Input::get('card_id');
        
        //check if cardId is from provider (for security)
        $payment = Payment::find($cardId);
        if($payment->provider_id != $providerId) {
            return Response::json(array('success' => false));
        } else {
            return $this->saveCardJuno($cardId, $creditCardHash);
        }
    }

    /**
     * @api{post}/libs/gateways/juno/add_card/provider
     * @return Json
     */
    public function saveCardJunoUser()
    {
        $userId = Input::get('id');
        $creditCardHash = Input::get('credit_card_hash');
        $cardId = Input::get('card_id');
        
        //check if cardId is from user (for security)
        $payment = Payment::find($cardId);
        if($payment->user_id != $userId) {
            return Response::json(array('success' => false));
        } else {
            return $this->saveCardJuno($cardId, $creditCardHash);
        }
    }

    public function saveCardJuno($cardId, $creditCardHash)
    {
        $payment = Payment::find($cardId);
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