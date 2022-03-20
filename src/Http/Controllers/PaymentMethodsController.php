<?php

namespace Codificar\PaymentGateways\Http\Controllers;

use App\Http\Controllers\Controller;
use Codificar\PaymentGateways\Http\Requests\PaymentsMethodFormRequest;
use Codificar\PaymentGateways\Http\Resources\PaymentMethodsResource;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
class PaymentMethodsController extends Controller
{
    public $keysPaymentMethods =  [
        'payment_money',
        'payment_card',
        'payment_balance',
    ];

    public $valuesPayment =  [
         ['payment_card_value' => 0],
         ['money_value' => 1],
         ['balance_value' => 3]
    ];
  
    /**
     * Recupera os métodos de pagamento ativos
     * @return View
     */
    public function getPaymentMethods(PaymentsMethodFormRequest $request)
    {
       $this->response = $this->message = [];
       $this->response = [];
       $this->statusCode = Response::HTTP_OK;
       $paymentMethods = array();
       try{
           //pega os metodos de pagamentos
           for ($i = 0 ; $i < count($this->keysPaymentMethods); $i++) {
               $paymentMethods[$i] = [
                   (bool) \Settings::findByKey($this->keysPaymentMethods[$i]),
                   $this->valuesPayment[$i]
                ];
            }   
            $this->response = $paymentMethods;
            } catch (\Throwable $e) {
            Log::error('RequestController, getRequestDetailsById() ' . $e->getMessage());
            $this->message = [
                'message' => trans('api.bad_request')
            ];
            $this->statusCode = Response::HTTP_BAD_REQUEST;
         }

         return (new PaymentMethodsResource($this->response))
         ->additional($this->message)
         ->response()->setStatusCode($this->statusCode);
    }
}