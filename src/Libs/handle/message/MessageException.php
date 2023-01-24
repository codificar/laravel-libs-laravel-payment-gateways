<?php
namespace Codificar\PaymentGateways\Libs\handle\message;

class MessageException
{

    /**
     * Handle Server message error Exceptions
     * @param String $message error message
     * @param String $exceptionName name of exception to treat in message
	 * @param Boolean $isSendRocket send or not message to rocket error channel | default: false
	 * @param Boolean $isSendEmail send or not message to Admin Email | default: true
     * @return String Error messge to response
     */
    public static function handleMessageException(String $message, String $exceptionName) {
		$errorMessage = "ERROR $exceptionName: " . $message . ' ' . trans('paymentGateway::paymentError.refused') ;
        return $errorMessage;
    }

	 /**
     * Handle Server message error Exceptions
     * @param String $message error message
     * @param Boolean $isSendRocket send or not message to rocket error channel | default: false
	 * @param Boolean $isSendEmail send or not message to Admin Email | default: true
     * @return String Error messge to response
     */
    public static function handleMessageServerException(String $message) {
		$exceptionName = 'ServerException';

        switch ($message) {
            case self::contains($message, '500 Internal Server Error'):
                $errorMessage = trans('paymentGateway::paymentError.500');
                break;
            case self::contains($message, '504 Gateway Timeout'):
                $errorMessage = trans('paymentGateway::paymentError.504');
                break;
            default:
            	$errorMessage = "ERROR $exceptionName: " . $message . ' ' . trans('paymentGateway::paymentError.refused') ;
                break;
        }
        return $errorMessage;
    }

	 /**
     * Handle Server message error Exceptions
     * @param String $message error message
     * @param Boolean $isSendRocket send or not message to rocket error channel | default: false
	 * @param Boolean $isSendEmail send or not message to Admin Email | default: true
     * @return String Error messge to response
     */
    public static function handleMessageAdiqException(String $message) {
		$gateway = 'adiq';

        switch ($message) {
            case self::contains($message, 'ERRO INTERNO'):
                $errorMessage = trans('paymentGateway::paymentError.adiq_internal_error');
                break;
            case self::contains($message, '91-transaction NAO AUTORIZADA'):
                $errorMessage = trans('paymentGateway::paymentError.adiq_error_brand');
                break;
            default:
            	$errorMessage = "ERROR $gateway: " . $message . ' ' . trans('paymentGateway::paymentError.refused') ;
                break;
        }
        return $errorMessage;
    }

	 /**
     * Handle Server message error Exceptions
     * @param String $message all object Exception to treat error message
     * @return String Error messge to response
     */
    public static function handleMessageIPagException(String $message) {
		$gateway = 'ipag';

        switch ($message) {
            case self::contains($message, 'Not Authorized'):
                $errorMessage = trans('paymentGateway::paymentError.not_authorized');
                break;
            case self::contains($message, 'Customer has been blacklisted'):
                $errorMessage = trans('paymentGateway::paymentError.customer_blacklisted');
                break;
            default:
            	$errorMessage = "ERROR $gateway: " . $message . ' ' . trans('paymentGateway::paymentError.refused') ;
                break;
        }
        return $errorMessage;
    }

	 /**
     * Handle Server message error Exceptions
     * @param String $err error message
     * @param Boolean $isSendRocket send or not message to rocket error channel | default: false
	 * @param Boolean $isSendEmail send or not message to Admin Email | default: true
     * @return String Error messge to response
     */
    public static function handleMessagePagarmeException(String $message) {
		$gateway = 'pagarme';

        switch ($message) {
            case self::contains($message, 'MESSAGE: Recipient não encontrado'):
                $errorMessage = trans('paymentGateway::paymentError.recipient_not_found_or_outdated');
                break;
            case self::contains($message, 'Card não encontrado'):
                $errorMessage = trans('paymentGateway::paymentError.card_not_registered');
                break;
            case self::contains($message, 'Invalid CPF'):
                $errorMessage = trans('paymentGateway::paymentError.transaction_declined_cpf');
                break;
            case self::contains($message, 'refused'):
                $errorMessage = trans('paymentGateway::paymentError.transaction_declined');
                break;
            case self::contains($message, '"number" is not allowed to be empty'):
                $errorMessage = trans('paymentGateway::paymentError.transaction_declined_address_number');
                break;
            case self::contains($message, '"neighborhood" is not allowed to be empty'):
                $errorMessage = trans('paymentGateway::paymentError.transaction_declined_neighborhood');
                break;
            case self::contains($message, 'invalid_parameter'):
                $message = explode("MESSAGE:", $message);
                $errorMessage = trim($message[1]);
                break;
            case self::contains($message, '"unit_price" must be an integer'):
                $errorMessage = trans('paymentGateway::paymentError.transaction_declined');
                break;
            case self::contains($message, '"phone_numbers" at position 0 fails'):
                $errorMessage = trans('paymentGateway::paymentError.transaction_declined');
                break;
            default:
            	$errorMessage = "ERROR $gateway: " . $message . ' ' . trans('paymentGateway::paymentError.refused') ;
                break;
        }
        return $errorMessage;
    }

    /**
	 * verify if message contains occourrences
	 * @param String $message message to verify
	 * @param String $search string to search
	 * @return boolean true if message contains occourrences
	 */
    public static function contains(String $message, String $search)
    {
        return strpos($message, $search) !== false
            ? true
            : false;
    }

}