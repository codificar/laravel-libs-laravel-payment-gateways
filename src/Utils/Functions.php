<?php
namespace Codificar\PaymentGateways\Utils;

class Functions
{

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