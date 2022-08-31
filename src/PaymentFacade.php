<?php 

namespace Codificar\PaymentGateways;

//use Omnipay\Common\CreditCard;
use Codificar\PaymentGateways\Libs\PaymentFactory;

class PaymentFacade extends \Illuminate\Support\Facades\Facade
{

    const PAYMENT_GATEWAY_PAGARME = 'pagarme';
    const PAYMENT_GATEWAY_PAGARMEv2 = 'pagarmev2';
    const PAYMENT_GATEWAY_STRIPE = 'stripe';
    const PAYMENT_GATEWAY_ZOOP = 'zoop';
    const PAYMENT_GATEWAY_CIELO = 'cielo';
    const PAYMENT_GATEWAY_BRASPAG = 'braspag';
    const PAYMENT_GATEWAY_DIRECTPAY = 'directpay';
    const PAYMENT_GATEWAY_GETNET = 'getnet';
    const PAYMENT_GATEWAY_BANCARD = 'bancard';
    const PAYMENT_GATEWAY_TRANSBANK = 'transbank';
    const PAYMENT_GATEWAY_GERENCIANET = 'gerencianet';
    const PAYMENT_GATEWAY_CARTO = 'carto';
    const PAYMENT_GATEWAY_BRAINTREE = 'braintree';
    const PAYMENT_GATEWAY_BYEBNK = 'byebnk';
    const PAYMENT_GATEWAY_BRASPAG_CIELO_ECOMMERCE = 'braspag_cielo_ecommerce';
    const PAYMENT_GATEWAY_BANCRYP = 'bancryp';
    const PAYMENT_GATEWAY_PAGARAPIDO = 'pagarapido';
    const PAYMENT_GATEWAY_ADIQ = 'adiq';
    const PAYMENT_GATEWAY_BANCO_INTER = 'bancointer';
    const PAYMENT_GATEWAY_IPAG = 'ipag';
    const PAYMENT_GATEWAY_JUNO = 'juno';

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
