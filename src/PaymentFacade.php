<?php 

namespace Codificar\PaymentGateways;

//use Omnipay\Common\CreditCard;
use Codificar\PaymentGateways\Libs\PaymentFactory;

class PaymentFacade extends \Illuminate\Support\Facades\Facade
{
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
