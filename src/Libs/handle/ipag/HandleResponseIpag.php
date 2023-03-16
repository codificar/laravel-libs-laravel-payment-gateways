<?php
namespace Codificar\PaymentGateways\Libs\handle\ipag;

use ApiErrors;
use Codificar\PaymentGateways\Libs\handle\message\MessageExceptionIpag;

class HandleResponseIpag
{

     /**
     * Payment status code
     */
    const CODE_CREATED          =   1;
    const CODE_WAITING_PAYMENT  =   2;
    const CODE_CANCELED         =   3;
    const CODE_IN_ANALISYS      =   4;
    const CODE_PRE_AUTHORIZED   =   5;
    const CODE_PARTIAL_CAPTURED =   6;
    const CODE_DECLINED         =   7;
    const CODE_CAPTURED         =   8;
    const CODE_CHARGEDBACK      =   9;
    const CODE_IN_DISPUTE       =   10;

    /**
     * Payment status string
     */
    const PAYMENT_NOTFINISHED  =   'not_finished';
    const PAYMENT_AUTHORIZED   =   'authorized';
    const PAYMENT_PAID         =   'paid';
    const PAYMENT_DENIED       =   'denied';
    const PAYMENT_VOIDED       =   'voided';
    const PAYMENT_REFUNDED     =   'refunded';
    const PAYMENT_PENDING      =   'pending';
    const PAYMENT_ABORTED      =   'aborted';
    const PAYMENT_SCHEDULED    =   'scheduled';

    const WAITING_PAYMENT = 'waiting_payment';
    
    const RESOURCE_SELLER = 'sellers';
    const RESOURCE_WEBHOOK = 'webhooks';

    /**
     * ERROR MESSAGES
     */
    const CUSTOMER_BLACK_LIST = 'Customer has been blacklisted';
    const DECLINED = 'DECLINED';

    public static $isSuccess              = false;
    public static $isData                 = false;
    public static $isWebhookList          = false;
    public static $isWebhookResponse      = false;
    public static $isAttributes           = false;
    public static $isSellerResponse       = false;
    public static $isStatus               = false;
    public static $isAcquirer             = false;
    public static $statusWaitingPayment   = false;
    public static $statusCreate           = false;
    public static $statusCaptured         = false;
    public static $statusPreAuth          = false;
    public static $isAprroved             = false;
    public static $isDenied               = false;

    /**
     * init var to use in request
     * @param $Object $response
     * @return void
     */
    public static function initVars($response)
    {
        self::$isSuccess = isset($response->success) && filter_var($response->success, FILTER_VALIDATE_BOOLEAN);
        self::$isData = isset($response->data) && !empty($response->data);
        self::$isWebhookList = self::$isData && isset($response->data->links) && !empty($response->data->links);
        self::$isWebhookResponse = self::$isData && isset($response->data->resource) && !empty($response->data->resource) && $response->data->resource == self::RESOURCE_WEBHOOK;
        self::$isAttributes = self::$isData && isset($response->data->attributes);
        self::$isSellerResponse = self::$isData && isset($response->data->resource) && $response->data->resource == self::RESOURCE_SELLER;
        self::$isStatus = self::$isAttributes && isset($response->data->attributes->status) && !empty($response->data->attributes->status);
        self::$isAcquirer = self::$isAttributes && isset($response->data->attributes->acquirer) && !empty($response->data->attributes->acquirer);
        self::$statusWaitingPayment = self::$isStatus && $response->data->attributes->status->code == self::CODE_WAITING_PAYMENT;
        self::$statusCreate = self::$isStatus && $response->data->attributes->status->code == self::CODE_CREATED;
        self::$statusCaptured = self::$isStatus && $response->data->attributes->status->code == self::CODE_CAPTURED;
        self::$statusPreAuth = self::$isStatus && $response->data->attributes->status->code == self::CODE_PRE_AUTHORIZED;
        self::$isAprroved = self::$statusCreate || self::$statusCaptured || self::$statusPreAuth;
        self::$isDenied = !self::$statusCreate || !self::$statusCaptured || !self::$statusPreAuth;
    }

    /**
     * Handle the message error and failure 
     * @param Object $response response from ipag gateway
     * @return array
     */
    public static function handle($response) 
    {
        try {
            self::initVars($response);
            // Em caso de sucesso retornar o data para manipular
            if( self::$isSuccess &&
                (   self::$isSellerResponse || self::$isWebhookResponse ||
                    self::$isWebhookList  ||
                    self::$isAprroved || self::$statusWaitingPayment
                ) 
            ) {
                return array(
                    'success' 		=> true,
                    'data' 		    => $response->data,
                    'message' 		=> trans('payment.success')
                );

            } else if( self::$isSuccess && self::$isAttributes 
                && self::$isDenied && !self::$statusWaitingPayment 
            ) {
                return self::getMessageError($response);
            } else if(!self::$isSuccess) {
                return self::getMessageFailure($response);
            } else {
                return self::getDefaultMessage($response);
            }
        } catch (\Exception $th) {
            \Log::error($th->getMessage() . $th->getTraceAsString());
			return array(
				"success" 	=> false ,
				'data' 	=> [],
				'error' 	=> array(
					"code" 		=> \ApiErrors::API_ERROR,
					"messages" 	=> array(trans('paymentgateway::paymentError.refused'))
				)
			);

        }
    }

    /**
     * Get Message error in request
     * @param Object $response repsonse request ipag 
     * @return array
     */
    public static function getMessageError($response)
    {
        $message = '';
        $code = -1;

        \Log::error('HandleResponseIpag > getMessageError: ' . json_encode($response));

        //verifica se tem mensagem de erro no status
        if(self::$isStatus) {
            $status = $response->data->attributes->status;
            if(isset($status->code)) {
                $code = $status->code;
            }

            if(isset($status->message)) {
                $message = $status->message;
            }
        } 
        
        // tenta capturar a mensagem de erro do acquirer
        if(self::$isAcquirer) {
            $acquirer = $response->data->attributes->acquirer;
            if(isset($acquirer->message)) {
                $message = $acquirer->message;
            }

            if(isset($acquirer->code)) {
                $code = $acquirer->code;
            }
        }

         return array(
            "success" 				=>  false,
            "type" 					=>  'api_ipag_error',
            "code" 					=>  $code,
            "message" 				=>  MessageExceptionIpag::handleMessageException($message),
            "original_message"      =>  $message,
            "response"              =>  json_encode($response),
            "transaction_id"		=>  '',
            'billet_expiration_date'=>  ''
         );

    }

    /**
     * Get Message error in failure request
     * @param Object $response repsonse request ipag 
     * @return array
     */
    public static function getMessageFailure($response)
    {
        $message = '';
        $code = -2;
    
        \Log::error('HandleResponseIpag > getMessageFailure: ' . json_encode($response));

        // verifica se tem uma mensagem direta 
        if(isset($response->message) && is_string($response->message)) {
            $message = json_decode($response->message);
            // converteu para json e vai capturar a mensagem de erro
            if(is_object($message)) {
                if(isset($message->code)) {
                    $code = $message->code;
                }
                $response = get_object_vars($message);
                // verifica se tem message
                if(isset($response['message'])) {
                    $message = $response['message'];
                    //verifica se o message tem objetos de erro
                    if(is_object($message)) {
                        $messagesArray = get_object_vars($message);
                        $message = self::implode_recursive(', ', $messagesArray);
                    }

                    return array(
                        "success" 				=>  false,
                        "type" 					=>  'api_ipag_error',
                        "code" 					=>  $code,
                        "message" 				=>  MessageExceptionIpag::handleMessageException($message),
                        "original_message"      =>  $message,
                        "response"              =>  json_encode($response),
                        "transaction_id"		=>  '',
                        'billet_expiration_date'=>  ''
                    );
                } 
                
                // verifica se tem obj error
                if(isset($response['error'])) {
                    $error = $response['error'];
                    if(isset($error->message)) {
                        $message = $error->message;
                    }

                    if(isset($error->code)) {
                        $code = $error->code;
                    }
                }

                if(isset($response['code'])) {
                    $code = $response['code'];    
                }
            // Não converteu para objeto e vai retornar a resposta completa
            } else {
                $message = $response->message;
            }
        } 
        
        if(self::$isData){
            return self::getMessageError($response);
        }


        return array(
            "success" 				=>  false,
            "type" 					=>  'api_ipag_error',
            "code" 					=>  $code,
            "message" 				=>  MessageExceptionIpag::handleMessageException($message),
            "original_message"      =>  $message,
            "response"              =>  json_encode($response),
            "transaction_id"		=>  '',
            'billet_expiration_date'=>  ''
        );
    }

    /**
     * Get Message error default
     * @param Object $response repsonse request ipag 
     * @return array
     */
    public static function getDefaultMessage($response)
    {
        \Log::error('HandleResponseIpag > getDefaultMessage: ' . json_encode($response));
        return array(
            "success" 				=>  false,
            "type" 					=>  'api_ipag_error',
            "code" 					=>  '',
            "message" 				=>  '',
            "original_message"      =>  '',
            "response"              =>  json_encode($response),
            "transaction_id"		=>  '',
            'billet_expiration_date'=>  ''
        );
    }

    /**
     * function to extract message by implode
     * @param string $separator separator to add in implode
     * @param array @array array to implode
     * @return string
     */
    public static function implode_recursive(string $separator, array $array): string
    {
        $string = '';
        foreach ($array as $i => $a) {
            if (is_array($a)) {
                $string .= self::implode_recursive($separator, $a);
            } else {
                $string .= $a;
                if ($i < count($array) - 1) {
                    $string .= $separator;
                }
            }
        }

        return $string;
    }

}