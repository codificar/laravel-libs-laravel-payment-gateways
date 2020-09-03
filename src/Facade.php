<?php 

namespace Codificar\PaymentGateways;

use Omnipay\Common\CreditCard;

class Facade extends \Illuminate\Support\Facades\Facade
{
    /**
     * @param  array  $parameters
     * @return CreditCard
     */
    public static function creditCard($parameters = null)
    {
        return new CreditCard($parameters);
    }

    /**
     * {@inheritDoc}
     */
    protected static function getFacadeAccessor()
    {
        return 'omnipay';
    }
}
