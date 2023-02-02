<?php
namespace Codificar\PaymentGateways\Libs\handle\message;

use Codificar\PaymentGateways\Utils\Functions;

class MessageExceptionPagarme
{
	 /**
     * Handle Server message error Exceptions
     * @param String $err error message
     * @param Boolean $isSendRocket send or not message to rocket error channel | default: false
	 * @param Boolean $isSendEmail send or not message to Admin Email | default: true
     * @return String Error messge to response
     */
    public static function handleMessagePagarmeException(String $message) {
        $message = strtolower($message);
        switch ($message) {
            case Functions::contains($message, 'message: recipient não encontrado'):
                return trans('paymentGateway::paymentError.recipient_not_found_or_outdated');
                break;
            case Functions::contains($message, 'card não encontrado'):
                return trans('paymentGateway::paymentError.card_not_registered');
                break;
            case Functions::contains($message, 'invalid cpf'):
                return trans('paymentGateway::paymentError.transaction_declined_cpf');
                break;
            case Functions::contains($message, 'refused'):
                return trans('paymentGateway::paymentError.transaction_declined');
                break;
            case Functions::contains($message, '"number" is not allowed to be empty'):
                return trans('paymentGateway::paymentError.transaction_declined_address_number');
                break;
            case Functions::contains($message, '"neighborhood" is not allowed to be empty'):
                return trans('paymentGateway::paymentError.transaction_declined_neighborhood');
                break;
            case Functions::contains($message, 'invalid_parameter'):
                $message = explode("message:", $message);
                return trim($message[1]);
                break;
            case Functions::contains($message, '"unit_price" must be an integer'):
                return trans('paymentGateway::paymentError.transaction_declined');
                break;
            case Functions::contains($message, '"phone_numbers" at position 0 fails'):
                return trans('paymentGateway::paymentError.transaction_declined');
                break;
            default:
            	return trans('paymentGateway::paymentError.refused') . ": $message" ;
                break;
        }
    }
}