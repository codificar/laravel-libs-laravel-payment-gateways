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
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;

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
        $response = $request['operation']['response'];

        if ($response !== "S") {
            return response()->json(['message' => 'Erro ao realizar a cobrança'], 400); 
        }
        return response()->json(['message' => 'Processamento concluído'], 200);
    }

    public function confirmPaymentWebHookBancard(Request $request) {

        $shop_id = (string)$request['operation']['shop_process_id'];
        $response = $request['operation']['response'];

        if ($response !== "S") {
            return response()->json(['message' => 'Erro ao realizar a cobrança'], 400); 
        }

        // Inicia a tentativa de localizar a transação com um máximo de tentativas e intervalo entre elas
        $maxAttempts = 10;
        $attemptDelay = 10; 

        for ($attempts = 0; $attempts < $maxAttempts; $attempts++) {
            try {
                $transaction = Transaction::where('gateway_transaction_id', $shop_id)->firstOrFail();
                
                // Se a transação for encontrada, prossegue com o processamento
                Transaction::changeStatusForPaid($transaction);
                Requests::changeIsPaidToSuccess($transaction->request_id);

                return response()->json(['message' => 'Processamento concluído'], 200);
            } catch (ModelNotFoundException $e) {
                if ($attempts < $maxAttempts - 1) {
                    sleep($attemptDelay);
                } else {
                    // Loga a falha após esgotar as tentativas
                    Log::error('Transação não encontrada após várias tentativas', ['shop_id' => $shop_id]);
                    return response()->json(['message' => 'Transação não encontrada'], 404);
                }
            } catch (\Exception $e) {
                Log::error('Erro ao processar transação', ['error' => $e->getMessage()]);
                return response()->json(['message' => 'Erro interno do servidor'], 500);
            }
        }
    }
}
