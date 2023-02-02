<?php
namespace Codificar\PaymentGateways\Libs\handle\message;

use Codificar\PaymentGateways\Utils\Functions;

class MessageExceptionAdiq
{

	 /**
     * Handle Server message error Exceptions
     * @param String $message error message
     * @param Boolean $isSendRocket send or not message to rocket error channel | default: false
	 * @param Boolean $isSendEmail send or not message to Admin Email | default: true
     * @return String Error messge to response
     */
    public static function handleMessageException(String $message) {
        $message = strtolower($message);
        switch ($message) {
            case Functions::contains($message, 'erro interno'):
                return trans('paymentGateway::paymentError.adiq_internal_error');
                break;
            case Functions::contains($message, '91-transaction nao autorizada'):
                return trans('paymentGateway::paymentError.adiq_error_brand');
                break;
            default:
                return trans('paymentGateway::paymentError.refused') . ": $message" ;
                break;
        }
    }
}