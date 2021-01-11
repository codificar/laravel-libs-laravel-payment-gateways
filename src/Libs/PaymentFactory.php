<?php

namespace Codificar\PaymentGateways\Libs;

use Settings;
use Codificar\PaymentGateways\PaymentFacade;

class PaymentFactory
{

    public static function createGateway()
    {
        switch (Settings::findByKey('default_payment')) {
            case PaymentFacade::PAYMENT_GATEWAY_PAGARME:
                return (new PagarmeLib());

            case PaymentFacade::PAYMENT_GATEWAY_STRIPE:
                return (new StripeLib());

            case PaymentFacade::PAYMENT_GATEWAY_ZOOP:
                return (new ZoopLib());

            case PaymentFacade::PAYMENT_GATEWAY_CIELO:
                return (new CieloLib());

            case PaymentFacade::PAYMENT_GATEWAY_BRASPAG:
                return (new BrasPagLib());

            case PaymentFacade::PAYMENT_GATEWAY_GETNET:
                return (new GetNetLib());

            case PaymentFacade::PAYMENT_GATEWAY_DIRECTPAY:
                return (new DirectPayLib());

            case PaymentFacade::PAYMENT_GATEWAY_BANCARD:
                return (new BancardLib());
            case PaymentFacade::PAYMENT_GATEWAY_TRANSBANK:
                return (new TransbankLib());

            case PaymentFacade::PAYMENT_GATEWAY_GERENCIANET:
                return (new GerenciaNetLib());
        }
    }

    public static function createBilletGateway()
    {
        switch (Settings::findByKey('default_payment_boleto')) {
            case PaymentFacade::PAYMENT_GATEWAY_GERENCIANET:
                return (new GerenciaNetLib());
        }
    }
}
