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
                return trans('paymentgateway::paymentError.not_authorized');
                break;
            case Functions::contains($message, 'nao autorizado pelo emissor'):
                return trans('paymentgateway::paymentError.card_not_authorized');
                break;
            case Functions::contains($message, 'customer has been blacklisted'):
                return trans('paymentgateway::paymentError.customer_blacklisted');
                break;
            case Functions::contains($message, 'cardtoken is no longer valid'):
            case Functions::contains($message, 'is not a valid'):
                return trans('paymentgateway::paymentError.customer_card_invalid');
                break;
            case Functions::contains($message, 'canceled'):
                return trans('paymentgateway::paymentError.canceled');
                break;
            case Functions::contains($message, '504'):
                return trans('paymentgateway::paymentError.504');
                break;
            default:
                return trans('paymentgateway::paymentError.refused') . ": $message" ;
                break;
        }
    }  

}