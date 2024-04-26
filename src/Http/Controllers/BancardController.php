<?php

namespace Codificar\PaymentGateways\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

//FormRequest
use Codificar\PaymentGateways\Http\Requests\AddCardBancardFormRequest;
//Resources
use Codificar\PaymentGateways\Http\Resources\AddCardBancardResource;

use Codificar\PaymentGateways\Libs\BancardApi;
use Codificar\PaymentGateways\Libs\PaymentFactory;

use View, User, Provider, Input;
use Payment;
use Transaction;
use Illuminate\Support\Facades\DB;
use Requests;


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
    public function getReturn(Request $request)
    {
        $user_id = $request->query('user_id');
        $provider_id = $request->query('provider_id');

        $user = User::find($user_id);
        $provider = Provider::find($provider_id);

        $status = $request->query('status');
        $description = $request->query('description');

        if ($status == self::SUCCESS) {
            $gateway = PaymentFactory::createGateway();
            // Chama a função para buscar os cartões e salvar no banco
            BancardApi::getCards($gateway->public_key, $gateway->private_key, $provider, $user);
        }

        //retorno para view
        return View::make('gateways::bancard.result', compact('status', 'description'));
    }

    public function confirmPaymentWebHook(Request $request){

        DB::transaction(function () use ($request) {
            $transaction = Transaction::where('gateway_transaction_id', $request->shop_process_id)->first();

            if ($transaction && $request->response_code == 00 && $request->response === "S") {
                Transaction::changeStatusForPaid($transaction->id);
                Requests::changeIsPaidToSuccess($transaction->request_id);
            }
        });

        return response()->json(['message' => 'Processamento concluído'], 200);
    }
}
