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
                return trans('paymentgateway::paymentError.recipient_not_found_or_outdated');
                break;
            case Functions::contains($message, 'card não encontrado'):
                return trans('paymentgateway::paymentError.card_not_registered');
                break;
            case Functions::contains($message, 'invalid cpf'):
                return trans('paymentgateway::paymentError.transaction_declined_cpf');
                break;
            case Functions::contains($message, 'refused'):
                return trans('paymentgateway::paymentError.transaction_declined');
                break;
            case Functions::contains($message, '"number" is not allowed to be empty'):
                return trans('paymentgateway::paymentError.transaction_declined_address_number');
                break;
            case Functions::contains($message, '"neighborhood" is not allowed to be empty'):
                return trans('paymentgateway::paymentError.transaction_declined_neighborhood');
                break;
            case Functions::contains($message, 'invalid_parameter'):
                $message = explode("message:", $message);
                return trim($message[1]);
                break;
            case Functions::contains($message, 'fails because'):
                $message = explode("fails because", $message);
                return trim(last($message));
                break;
            case Functions::contains($message, '"unit_price" must be an integer'):
                return trans('paymentgateway::paymentError.transaction_declined');
                break;
            case Functions::contains($message, '"phone_numbers" at position 0 fails'):
                return trans('paymentgateway::paymentError.transaction_declined');
                break;
            case Functions::contains($message, 'recebedor do administrador não foi encontrado'):
                return trans('paymentgateway::paymentError.admin_recipient_not_found');
            default:
            	return trans('paymentgateway::paymentError.refused') . ": $message" ;
                break;
        }
    }
}