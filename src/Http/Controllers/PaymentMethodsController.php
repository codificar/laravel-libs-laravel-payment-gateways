<?php

namespace Codificar\PaymentGateways\Http\Controllers;

use App\Http\Controllers\Controller;

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
     * Recupera settings e acessa view
     * @return View
     */
    public function getPaymentMethods()
    {
        //pega os metodos de pagamentos
        $paymentMethods = array();
        foreach ($this->keysPaymentMethods as $key) {
            $paymentMethods[$key] = (bool) \Settings::findByKey($key);
        }   

        return $paymentMethods;
    }
}