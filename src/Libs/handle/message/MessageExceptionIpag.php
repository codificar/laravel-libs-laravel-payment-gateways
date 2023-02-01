<?php
namespace Codificar\PaymentGateways\Libs\handle\message;

use Codificar\PaymentGateways\Utils\Functions;

class MessageExceptionIpag
{

	 /**
     * Handle Server message error Exceptions
     * @param String $message all object Exception to treat error message
     * @return String Error messge to response
     */
    public static function handleMessageException(String $message) {
        $message = strtolower($message);
        switch ($message) {
            case Functions::contains($message, 'not authorized'):
                return trans('paymentGateway::paymentError.not_authorized');
                break;
            case Functions::contains($message, 'nao autorizado pelo emissor'):
                return trans('paymentGateway::paymentError.card_not_authorized');
                break;
            case Functions::contains($message, 'customer has been blacklisted'):
                return trans('paymentGateway::paymentError.customer_blacklisted');
                break;
            case Functions::contains($message, 'cardtoken is no longer valid'):
            case Functions::contains($message, 'is not a valid'):
                return trans('paymentGateway::paymentError.customer_card_invalid');
                break;
            case Functions::contains($message, '504'):
                return trans('paymentGateway::paymentError.504');
                break;
            default:
            	return $message . ' ' . trans('paymentGateway::paymentError.refused');
                break;
        }
    }  

}