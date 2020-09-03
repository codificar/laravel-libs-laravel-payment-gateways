<?php

namespace Codificar\PaymentGateways\Libs;

use Settings;

class PaymentFactory
{
    const PAYMENT_GATEWAY_PAGARME = 'pagarme';
    const PAYMENT_GATEWAY_STRIPE = 'stripe';
    const PAYMENT_GATEWAY_ZOOP = 'zoop';
    const PAYMENT_GATEWAY_BANCARD = 'bancard';
    const PAYMENT_GATEWAY_GERENCIANET = 'gerencianet';

    public static function createGateway()
    {

        switch (Settings::findByKey('default_payment')) {
            case self::PAYMENT_GATEWAY_PAGARME:
                return (new PagarmeLib());

            case self::PAYMENT_GATEWAY_STRIPE:
                return (new StripeLib());

            case self::PAYMENT_GATEWAY_ZOOP:
                return (new ZoopLib());

            case self::PAYMENT_GATEWAY_BANCARD:
                return (new BancardLib());

            case self::PAYMENT_GATEWAY_GERENCIANET:
                return (new \GerenciaNetLib());
        }
    }

    public static function createBilletGateway()
    {
        switch (Settings::findByKey('default_payment_boleto')) {
            case self::PAYMENT_GATEWAY_GERENCIANET:
                return (new \GerenciaNetLib());
        }
    }
}
