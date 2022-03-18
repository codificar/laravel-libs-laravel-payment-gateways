<?php

namespace Codificar\PaymentGateways\Http\Controllers;

use App\Http\Controllers\Controller;
use Codificar\PaymentGateways\Http\Requests\PaymentsMethodFormRequest;
use Codificar\PaymentGateways\Http\Resources\PaymentMethodsResource;

class PaymentMethodsController extends Controller
{
    public $keysPaymentMethods =  [
        'payment_money',
        'payment_card',
        'payment_machine',
        'payment_carto',
        'payment_crypt',
        'payment_debitCard',
        'payment_balance',
        'payment_prepaid',
        'payment_billing',
        'payment_direct_pix',
        'payment_gateway_pix'
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
       
       try{
            //pega os metodos de pagamentos
            $paymentMethods = array();
            foreach ($this->keysPaymentMethods as $key) {
                $paymentMethods[$key] = (bool) \Settings::findByKey($key);
            }   
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