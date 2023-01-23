<?php
namespace Codificar\PaymentGateways\Libs\handle\ipag;

use ApiErrors;
use Codificar\PaymentGateways\Libs\handle\message\MessageException;

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

    /**
     * ERROR MESSAGES
     */
    const CUSTOMER_BLACK_LIST = 'Customer has been blacklisted';
    const DECLINED = 'DECLINED';

    public static function handle($response) 
    {
        try {
            $isSuccess = isset($response->success) && filter_var($response->success, FILTER_VALIDATE_BOOLEAN);
            $isData = isset($response->data) && !empty($response->data);
            $isWebhookResponse = $isData && isset($response->data->data) && !empty($response->data->data);
            $isAttributes = $isData && isset($response->data->attributes);
            $isSellerResource = $isData && isset($response->data->resource) && $response->data->resource == self::RESOURCE_SELLER;
            $isStatus = $isAttributes && isset($response->data->attributes->status) && !empty($response->data->attributes->status);
            $isAcquirer = $isAttributes && isset($response->data->attributes->acquirer) && !empty($response->data->attributes->acquirer);
            $statusWaitingPayment = $isStatus && $response->data->attributes->status->code == self::CODE_WAITING_PAYMENT;
            $statusCreate = $isStatus && $response->data->attributes->status->code == self::CODE_CREATED;
            $statusCaptured = $isStatus && $response->data->attributes->status->code == self::CODE_CAPTURED;
            $statusPreAuth = $isStatus && $response->data->attributes->status->code == self::CODE_PRE_AUTHORIZED;
            
            // Em caso de sucesso retorn o data para maniular
            if( $isSuccess &&
                (   $isSellerResource || $isWebhookResponse || 
                    $statusCaptured || $statusWaitingPayment || 
                    $statusCreate || $statusPreAuth
                ) 
            ) {
                return array(
                    'success' 		=> true,
                    'data' 		    => $response->data,
                    'message' 		=> trans('payment.success')
                );

            } else if( $isSuccess && $isAttributes &&
                (!$statusCreate || !$statusCaptured && !$statusWaitingPayment || !$statusPreAuth) 
            ) {
                $message = '';
                $code = -1;

                //verifica se tem mensagem de erro no status
                if($isStatus) {
                    $status = $response->data->attributes->status;
                    $code = $status->code;
                    $message = $status->message;
                } 
                
                // tenta capturar a mensagem de erro do acquirer
                if($isAcquirer) {
                    $acquirer = $response->data->attributes->acquirer;
                    if(isset($acquirer->message)) {
                        $message = $acquirer->message;
                    }
                }

                \Log::error('HandleResponseIpag > Error 1:' . json_encode($response));
                
                return array(
                    "success" 				=>  false,
                    "type" 					=>  'api_ipag_error',
                    "code" 					=>  $code,
                    "message" 				=>  MessageException::handleMessageIPagException($message),
                    "original_message"      =>  $message,
                    "response"              =>  json_encode($response),
                    "transaction_id"		=>  '',
                    'billet_expiration_date'=>  ''
                );
            } else if(!$isSuccess) {
                $message = '';
                $code = -2;

                // verifica se tem uma mensagem direta 
                if(isset($response->message)) {
                    if(is_string($response->message)) {
                        $message = json_decode($response->message);
                        // converteu para json e vai capturar a mensagem de erro
                        if(is_object($message)) {
                            $response = get_object_vars($message);
                            // verifica se tem message
                            if(isset($response['message'])) {
                                $message = $response['message'];
                                //verifica se o message tem objetos de erro
                                if(is_object($message)) {
                                    $messagesArray = get_object_vars($message);
                                    foreach($messagesArray as $messageArray) {
                                        if(is_string($messageArray)) {
                                            $message = trim($messageArray);
                                        }
                                        if(is_array($messageArray)) {
                                            $message = $messageArray[0];    
                                        }
                                    }
                                }
                            } 
                            // verifica se tem obj error
                            else if(isset($response['error'])) {
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
                // verifica se tem mensagem no escopo da responsta
                } else if($isData){

                    if($isAcquirer) {
                        $acquirer = $response->data->attributes->acquirer;
                        if(isset($acquirer->message)) {
                            $message = $acquirer->message;
                        }
                    } else if($isStatus) {
                        $status = $response->data->attributes->status;
                        $code = $status->code;
                        $message = $status->message;
                    }
                }

                \Log::error('HandleResponseIpag > Error 2' . json_encode($response));

                return array(
                    "success" 				=>  false,
                    "type" 					=>  'api_ipag_error',
                    "code" 					=>  $code,
                    "message" 				=>  MessageException::handleMessageIPagException($message),
                    "original_message"      =>  $message,
                    "response"              =>  json_encode($response),
                    "transaction_id"		=>  '',
                    'billet_expiration_date'=>  ''
                );

            } else {
                \Log::error('HandleResponseIpag > Error 3' . json_encode($response));
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

        } catch (\Exception $th) {
            \Log::error($th->getMessage() . $th->getTraceAsString());

			return array(
				"success" 	=> false ,
				'data' 	=> [],
				'error' 	=> array(
					"code" 		=> ApiErrors::API_ERROR,
					"messages" 	=> array(trans('paymentGateway::paymentError.refused'))
				)
			);

        }
    }

}