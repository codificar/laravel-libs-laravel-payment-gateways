<?php

namespace Codificar\PaymentGateways\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

//FormRequest
use Codificar\PaymentGateways\Http\Requests\AddCardBancardFormRequest;
//Resources
use Codificar\PaymentGateways\Http\Resources\AddCardBancardResource;

use Codificar\PaymentGateways\Libs\BancardApi;

use View, User, Input;
use Payment;

class BancardController extends Controller
{

    const SUCCESS = "add_new_card_success";

    /**
     * @apiDescription Adiciona cartão do usuário para a Bancard
     * @author  Andre Gustavo <andre.gustavo@codificar.com.br>
     * @return Json
     */
    public function addCard(AddCardBancardFormRequest $request)
    {
        //get bancard iframe
        $gateway = \PaymentFactory::createGateway();
        $iframe = $gateway->createCard(new Payment, $request->user, $request->provider);
        
        //validação de sucesso com retorno de iframe
        return new AddCardBancardResource(['iframe' => $iframe]);
    }

    /**
     * {get}/libs/gateways/bancard/iframe_card/{process_id}
     * @param $process_id - id de processamento para criação do cartão
     * @return View
     */
    public function getIframeCard($process_id)
    {
        return View::make('gateways::bancard.new_card')->with(array(
            'process_id' => $process_id,
            'bancard_url_prod' => BancardApi::$APP_API_PROD,
            'bancard_url_dev' => BancardApi::$APP_API_DEV
        ));
    }

    /**
     * {get}/bancard/return
     * @return Json
     */
    public function getReturn($user_id, $provider_id)
    {
        if ($user_id)
            $user = \User::find($user_id);
        else
            $user = null;

        if ($provider_id)
            $provider = \Provider::find($provider_id);
        else
            $provider = null;

        $status = Input::get('status');
        $description = Input::get('description');

        if ($status == self::SUCCESS) {
            $gateway = \PaymentFactory::createGateway();
            //busca cartões do user na bancard e salva no banco
            BancardApi::getCards($gateway->public_key, $gateway->private_key, $user, $provider);

            /* $payment = null;
            if (isset($cards->status) && $cards->status == BancardApi::STATUS_SUCCESS) {
                foreach ($cards->cards as $card) {
                    $payment = Payment::whereCardToken($card->card_id)->first();
                }
            } */
        }

        //retorno para view
        return View::make('gateways::bancard.result')->with(array('status' => $status, 'description' => $description));

        //validação de sucesso com retorno de iframe
        //return new AddCardBancardUserResource(['status' => $status, 'description' => $description, 'payment' => $payment]);
    }
}
