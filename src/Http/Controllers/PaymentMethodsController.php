<?php

namespace Codificar\PaymentGateways\Http\Controllers;

use App\Http\Controllers\Controller;
use Codificar\PaymentGateways\Http\Requests\PaymentsMethodFormRequest;
use Codificar\PaymentGateways\Http\Resources\PaymentMethodsResource;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Codificar\PaymentGateways\Models\GatewaysLibModel;


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
}