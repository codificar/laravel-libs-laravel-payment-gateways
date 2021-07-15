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

            case PaymentFacade::PAYMENT_GATEWAY_BRAINTREE:
                return (new BraintreeLib());

            case PaymentFacade::PAYMENT_GATEWAY_BYEBNK:
                return (new ByeBankLib());

            case PaymentFacade::PAYMENT_GATEWAY_ZOOP:
                return (new ZoopLib());

            case PaymentFacade::PAYMENT_GATEWAY_CIELO:
                return (new CieloLib());

            case PaymentFacade::PAYMENT_GATEWAY_BRASPAG:
                return (new BrasPagLib());

            case PaymentFacade::PAYMENT_GATEWAY_BRASPAG_CIELO_ECOMMERCE:
                return (new BraspagCieloEcommerceLib());

            case PaymentFacade::PAYMENT_GATEWAY_BANCRYP:
                return (new BancrypLib());

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

            case PaymentFacade::PAYMENT_GATEWAY_CARTO:
                return (new CartoLib());

            case PaymentFacade::PAYMENT_GATEWAY_PAGARAPIDO:
                return (new PagarapidoLib());

            case PaymentFacade::PAYMENT_GATEWAY_ADIQ:
                return (new AdiqLib());

            case PaymentFacade::PAYMENT_GATEWAY_IPAG:
                return (new IpagLib());
        }
    }

    public static function createBilletGateway()
    {
        switch (Settings::findByKey('default_payment_boleto')) {
            case PaymentFacade::PAYMENT_GATEWAY_GERENCIANET:
                return (new GerenciaNetLib());
        }
    }

    public static function isZoop(){
        return (Settings::findByKey('default_payment') == PaymentFacade::PAYMENT_GATEWAY_ZOOP) ;
    }

    public static function cartoGateway(){
        return(new CartoLib());
    }

    public static function BancrypGateway(){
        return(new BancrypLib());
    }
}
