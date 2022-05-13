<?php

namespace Codificar\PaymentGateways\Http\Controllers;

use App\Http\Controllers\Controller;
use Codificar\PaymentGateways\Http\Requests\PaymentsMethodFormRequest;
use Codificar\PaymentGateways\Http\Resources\PaymentMethodsResource;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Codificar\PaymentGateways\Models\GatewaysLibModel;

use Illuminate\Http\Resources\Json\JsonResource;


class PaymentMethodsController extends Controller
{
    
    /**
     * Recupera os métodos de pagamento ativos
     * @return View
     */
    public function getPaymentMethods(PaymentsMethodFormRequest $request)
    {
       $this->response = $this->message = [];
       $this->response = [];
       $this->statusCode = Response::HTTP_OK;

       try{
            //pega os metodos de pagamentos
            $this->response = GatewaysLibModel::getPaymentsAvailable();
        } 
        catch (\Throwable $e) {
            Log::error($e->getMessage());
            $this->message = [
                'message' => trans('api.bad_request')
            ];
            $this->statusCode = Response::HTTP_BAD_REQUEST;
         }

         return (new PaymentMethodsResource(['response' => $this->response]))
         ->additional($this->message)
         ->response()->setStatusCode($this->statusCode);
    }

     /**
     * Recupera os métodos do provider
     * @return JsonResource
     */
    public function getProviderPayments()
    {
        $token = request()->token;
        $provider_id = request()->id;

        $providerPaymentsId = \ProviderPayments::getProviderPayments($provider_id)->makeHidden(['provider_id', 'id', 'created_at', 'updated_at']);

        $response_array = array(
            'success' => true,
            'provider_payments' => $providerPaymentsId,
        );
        $response_code = 200;
    
        return (new JsonResource($response_array))
         ->response()->setStatusCode($response_code);
    }

    /**
     * Salva os métodos do provider
     * @return JsonResource
     */
    public function setProviderPayments()
    {
        
        $provider_id = request()->id;
        $payment_to_enable = request()->provider_payment;

        \ProviderPayments::setProviderPayments($provider_id,$payment_to_enable);

        $response_array = array(
            'success' => true,
        );
        $response_code = 200;
           
        return (new JsonResource($response_array))
         ->response()->setStatusCode($response_code);
    }
   
}