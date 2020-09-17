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
        switch (Settings::findByKey('default_payment')) {
            case self::PAYMENT_GATEWAY_PAGARME:
                return (new PagarmeLib());
        }
    }
}
