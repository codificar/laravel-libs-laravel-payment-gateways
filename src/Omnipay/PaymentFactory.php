<?php

namespace Codificar\PaymentGateways\Omnipay;

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
        $parameters = [];
        switch (Settings::findByKey('default_payment')) {
            case self::PAYMENT_GATEWAY_PAGARME:
                $omnipay = "Pagarme";
                $parameters['apiKey'] = Settings::findByKey('pagarme_api_key');
                return (new PagarmeLib($omnipay, $parameters));
                break;

            case self::PAYMENT_GATEWAY_STRIPE:
                $omnipay = "Stripe";
                $parameters['apiKey'] = Settings::findByKey('stripe_secret_key');
                return (new StripeLib($omnipay, $parameters));
                break;

            case self::PAYMENT_GATEWAY_ZOOP:
                $omnipay = "Zoop";
                return (new GatewayLib($omnipay, $parameters));
                break;

            case self::PAYMENT_GATEWAY_BANCARD:
                $omnipay = "Bancard";
                return (new GatewayLib($omnipay, $parameters));
                break;
        }
    }
}
