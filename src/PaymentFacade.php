<?php 

namespace Codificar\PaymentGateways;

//use Omnipay\Common\CreditCard;
use Codificar\PaymentGateways\Libs\PaymentFactory;

class PaymentFacade extends \Illuminate\Support\Facades\Facade
{

    const PAYMENT_GATEWAY_PAGARME = 'pagarme';
    const PAYMENT_GATEWAY_STRIPE = 'stripe';
    const PAYMENT_GATEWAY_ZOOP = 'zoop';
    const PAYMENT_GATEWAY_CIELO = 'cielo';
    const PAYMENT_GATEWAY_BRASPAG = 'braspag';
    const PAYMENT_GATEWAY_DIRECTPAY = 'directpay';
    const PAYMENT_GATEWAY_GETNET = 'getnet';
    const PAYMENT_GATEWAY_BANCARD = 'bancard';
    const PAYMENT_GATEWAY_TRANSBANK = 'transbank';
    const PAYMENT_GATEWAY_GERENCIANET = 'gerencianet';

    /**
     * @param  array  $parameters
     * @return createGateway
     */
    public static function createGateway()
    {
        return (new PaymentFactory())::createGateway();
    }

    /**
     * {@inheritDoc}
     */
    protected static function getFacadeAccessor()
    {
        return 'PaymentFactory';
    }
}
