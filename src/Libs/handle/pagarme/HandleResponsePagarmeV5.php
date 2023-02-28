<?php
namespace Codificar\PaymentGateways\Libs\handle\pagarme;

use Codificar\PaymentGateways\Libs\handle\message\MessageExceptionPagarme;

class HandleResponsePagarmeV5
{

    
    /**
     * Payment status strings
     */
    const GATEWAY_PAID						=	"paid";
    const GATEWAY_FAILED					=	"failed";
    const GATEWAY_VOIDED					=	"voided";
    const GATEWAY_CAPTURED					=	"captured";
    const GATEWAY_REFUNDED					=	"refunded";
    const GATEWAY_OVERPAID					=	"overpaid";
    const GATEWAY_GENERATED					=	"generated";
    const GATEWAY_UNDERPAID					=	"underpaid";
    const GATEWAY_PROCESSING				=	"processing";
    const GATEWAY_WITH_ERROR				=	"with_error";
    const GATEWAY_PARTIAL_VOID				=	"partial_void";
    const GATEWAY_NOT_AUTHORIZED			=	"not_authorized";
    const GATEWAY_PARTIAL_CAPTURE			=	"partial_capture";
    const GATEWAY_WAITING_CAPTURE			=	"waiting_capture";
    const GATEWAY_ERROR_ON_VOIDING			=	"error_on_voiding";
    const GATEWAY_PARTIAL_REFUNDED			=	"partial_refunded";
    const GATEWAY_ERROR_ON_REFUNDING		=	"error_on_refunding";
    const GATEWAY_WAITING_CANCELATION		=	"waiting_cancellation";
    const GATEWAY_AUTHORIZED_PENDING_CAPTURE=	"authorized_pending_capture";

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
    const PAYMENT_ERROR        =   'error';

    const WAITING_PAYMENT = 'waiting_payment';

    public static $isSuccess                = false;
    public static $isData                   = false;
    public static $isCharge                 = false;
    public static $isLastTransaction        = false;
    public static $isGatewayResponse        = false;
    public static $isStatus                 = false;
    public static $isFailed                 = false;
    public static $isErrors                 = false;
    public static $isCaptured               = false;
    public static $isAuthorizedCaptured     = false;
    public static $isAprroved               = false;

    /**
     * init var to use in request
     * @param $Object $response
     * @return void
     */
    public static function initVars($response)
    {
        self::$isSuccess = isset($response->success) && filter_var($response->success, FILTER_VALIDATE_BOOLEAN);
        self::$isData = isset($response->data) && !empty($response->data);
        self::$isStatus = self::$isData && isset($response->data->status);
        self::$isFailed = self::$isStatus && $response->data->status == self::GATEWAY_FAILED;

        self::$isCharge = self::$isData && isset($response->data->charges[0]);
        self::$isLastTransaction = self::$isCharge && isset($response->data->charges[0]->last_transaction);
        self::$isGatewayResponse = self::$isLastTransaction && isset($response->data->charges[0]->last_transaction->gateway_response);
        self::$isErrors = self::$isLastTransaction && isset($response->data->charges[0]->last_transaction->gateway_response->errors);

        self::$isCaptured = self::$isLastTransaction && $response->data->charges[0]->last_transaction->status == self::GATEWAY_CAPTURED;
        self::$isAuthorizedCaptured = self::$isLastTransaction && $response->data->charges[0]->last_transaction->status == self::GATEWAY_AUTHORIZED_PENDING_CAPTURE;
        self::$isAprroved = self::$isCaptured || self::$isAuthorizedCaptured;


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
            // Em caso de sucesso retorn o data para manipular
            if( self::$isSuccess && !self::$isFailed && self::$isAprroved) {
                return array(
                    'success' 		=> true,
                    'data' 		    => $response,
                    'message' 		=> trans('payment.success')
                );

            } else if( self::$isSuccess && self::$isFailed && self::$isErrors) {
                return self::getMessageError($response);
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

        \Log::error('HandleResponsePagarmeV5 > getMessageError: ' . json_encode($response));

        //verifica se tem mensagem de erro no status
        if(self::$isGatewayResponse) {
            $gatewayResponse = $response->data->charges[0]->last_transaction->gateway_response;
            if(isset($gatewayResponse->code)) {
                $code = $gatewayResponse->code;
            }

            if(is_array($gatewayResponse->errors)) {
                $message = self::implode_recursive(', ', $gatewayResponse->errors);
            }
        } 

        return array(
            "success" 				=>  false,
            "type" 					=>  'api_pagarme_v5_error',
            "code" 					=>  $code,
            "message" 				=>  MessageExceptionPagarme::handleMessagePagarmeException($message),
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
        \Log::error('HandleResponsePagarmeV5 > getDefaultMessage: ' . json_encode($response));
        return array(
            "success" 				=>  false,
            "type" 					=>  'api_pagarme_v5_error',
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
            if(is_object($a)) {
                $a = get_object_vars($a);
            }
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