<?php
namespace Codificar\PaymentGateways\Libs\handle\message;

use Codificar\PaymentGateways\Utils\Functions;

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
            case Functions::contains($message, '500 Internal Server Error'):
                $errorMessage = trans('paymentGateway::paymentError.500');
                break;
            case Functions::contains($message, '504 Gateway Timeout'):
                $errorMessage = trans('paymentGateway::paymentError.504');
                break;
            default:
            	$errorMessage = "ERROR $exceptionName: " . $message . ' ' . trans('paymentGateway::paymentError.refused') ;
                break;
        }
        return $errorMessage;
    }

}