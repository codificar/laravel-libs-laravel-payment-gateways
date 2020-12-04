<?php

namespace Codificar\PaymentGateways\Libs;

//models do sistema
use Log, Exception;
use App;
use Payment;
use Provider;
use Transaction;
use User;
use LedgerBankAccount;
use Settings;

class DirectPayApi  
{
    const URL = "https://hml-gateway.directpay.com.br/api";

    const HOMOLOG_URL = "https://gateway.directpay.com.br/api";

    const POST_REQUEST = 'POST';
    const GET_REQUEST = 'GET';
    const DELETE_REQUEST = 'DELETE';
    const PUT_REQUEST = 'PUT';

    const APP_TIMEOUT = 200;

    const STATUS_FINISHED = 'finished';
    const STATUS_SUCCEEDED = 'SUCCEEDED';
    const STATUS_PROCESSED = 'PROCESSED';
    const STATUS_AUTHORIZED = 'AUTHORIZED';
    const STATUS_ERROR = 'error';
    const STATUS_CANCELED = 'canceled';
    const STATUS_CANCELLED = 'canceled';
    const STATUS_PENDING = 'pending';

    //Card Create
    public static function createCard($payment, $user)
    {
        $url = sprintf('%s/acquire/cardManager/add', self::URL);

        $cardNumber 			= $payment->getCardNumber();
		$cardExpirationMonth 	= $payment->getCardExpirationMonth();
		$cardExpirationYear 	= $payment->getCardExpirationYear();
		$cardCvc 				= $payment->getCardCvc();
		$cardHolder 			= $payment->getCardHolder();
		$userName				= $user->first_name." ".$user->last_name;
		$userDocument			= str_replace(".", "", $user->document);

		// $userDocument = $this->cleanCpf($user->document);

        $result = self::getCardHolder($userDocument);

        $cardHolderId = $result->data->cardHolder->entity->idUUID;
        $userName = $result->data->cardHolder->entity->name;
        $userEmail = $result->data->cardHolder->entity->email;
        
        $fields = array (
            'cardTypeUUID' => '62413356-e248-4a72-a8b7-2d8644fe33a8',
            'card' => (object)array(
                'name'              =>   $cardHolder,
                'pan'               =>   $cardNumber,
                'securityCode'      =>   $cardCvc,
                'expirationMonth'   =>   $cardExpirationMonth,
                'expirationYear'    =>   $cardExpirationYear
            ),
            'cardHolder'    =>  (object)array(
                'entity'    =>  (object)array(
                    // 'entityTypeId'      =>  1,
                    'idUUID'            =>  $cardHolderId,
                    'vatNumber'         =>  $userDocument,
                ),
            )
        );

        $body = json_encode($fields);

        $gateway = false;

        $header = self::getHeader($gateway);

        $requestType = self::POST_REQUEST;

        $apiRequest = self::apiRequest($url, $body, $header, $requestType);

        if ($apiRequest->success) {
            $payment->card_token = $apiRequest->data->cardUUID;
            $payment->customer_id = $cardHolderId;
            $payment->gateway = "directpay";
            $payment->save();
        }
        

        return $apiRequest;

    }

    //Delete Card
    public static function deleteCard($cardId)
    {

        $url = sprintf('%s/acquire/cardManager/remove/%s', self::URL, $cardId);

        $fields = null;

        $gateway = false;

        $header = self::getHeader($gateway);

        $card = self::getCard($cardId);

        if($card->success){
            $requestType = self::DELETE_REQUEST;

            $apiRequest = self::apiRequest($url, $fields, $header, $requestType);

            return $apiRequest;
        } else {
            return(array (
                'success'       =>  false,
                'message'       =>  'This is not a DirectPayCard. Please change the gateway'
            )
        ); 
        }

        
    }

    // Get card Info
    public static function getCard($cardId)
    {
        $url = sprintf('%s/acquire/cardManager/cardInfo/%s', self::URL, $cardId);

        $fields = null;

        $gateway = false;

        $header = self::getHeader($gateway);

        $requestType = self::GET_REQUEST;

        $apiRequest = self::apiRequest($url, $fields, $header, $requestType);

        return $apiRequest;
    }

    //Payment Transactions
    public static function chargeOrCapture($amount, $capture, $payment)
    {
        $url = sprintf('%s/acquire/card/authorize', self::URL);

        if($payment->card_token){
            $card = self::getCard($payment->card_token);
        } else {
            $user = User::find($payment->user_id);
            $card = self::createCard($payment, $user);
           
        }

        $cardId = $payment->card_token;

        // if($card->success && $payment->card_token == $card->data->card->cardUUID){
        //     $cardId = $payment->card_token;
        // } else {
            
            
            
        //     if($card->success && $payment->card_token == $card->data->card->cardUUID){
        //         $cardId = $payment->card_token;
        //     } else {
        //         return(array (
        //             'success'       =>  false,
        //             'message'       =>  'This is not a DirectPayCard. Please change the gateway'
        //         ));
        //     }
        // }

        $fields = array (
            'amount'            =>  $amount,
            'installments'      =>  1,
            'capture'           =>  $capture,
            'transactionType'   =>  1,
            'card'          =>  (object) array(
                'cardUUID'      =>  $cardId
                // "name"              => $payment->getCardHolder(),
                // "pan"               => $payment->getCardNumber(),
                // "securityCode"      => $payment->getCardCvc(),
                // "expirationMonth"   => $payment->getCardExpirationMonth(),
                // "expirationYear"    => $payment->getCardExpirationYear()
                 ),
            'softDescriptor'        => 'Cruzeiro Go',
            'processBillingOnline'  => true
        );

        $body = json_encode($fields);

        $gateway = true;

        $header = self::getHeader($gateway);

        $requestType = self::POST_REQUEST;

        $apiRequest = self::apiRequest($url, $body, $header, $requestType);

        return $apiRequest;
    }

    public static function capture($amount, $transaction)
    {
        $url = sprintf('%s/acquire/card/capture', self::URL);

        $fields = array (
            'paymentToken'          =>  $transaction->gateway_transaction_id,
            'amount'                =>  $amount
        );

        $body = json_encode($fields);

        $gateway = true;

        $header = self::getHeader($gateway);

        $requestType = self::PUT_REQUEST;

        $apiRequest = self::apiRequest($url, $body, $header, $requestType);

        return $apiRequest;
    }

    // Cancel Payment
    public static function cancelPayment($directTransactionId)
    {
        $url = sprintf('%s/acquire/card/reverse', self::URL);

        $transaction = Transaction::getTransactionByGatewayId($directTransactionId);

        $fields = array (
            'paymentToken'      =>  $directTransactionId,
            'amount'            =>  $transaction->gross_value
        );

        $body = json_encode($fields);

        $gateway = true;

        $header = self::getHeader($gateway);

        $requestType = self::PUT_REQUEST;

        $apiRequest = self::apiRequest($url, $body, $header, $requestType);

        return $apiRequest;
    }

    private function cleanCpf($cpf)
	{
		$cpf = trim($cpf);
		$cpf = str_replace(".", "", $cpf);
		$cpf = str_replace(",", "", $cpf);
		$cpf = str_replace("-", "", $cpf);
		$cpf = str_replace("/", "", $cpf);
		return $cpf;
	}

    // Get Payment Info
    public static function getPaymentInfo($directTransactionId)
    {
        $url = sprintf('%s/acquire/card/paymentInfo/%s', self::URL, $directTransactionId);

        $fields = null;

        $gateway = true;

        $header = self::getHeader($gateway, $directTransactionId);

        $requestType = self::GET_REQUEST;

        $apiRequest = self::apiRequest($url, $fields, $header, $requestType);

        return $apiRequest;
    }

    // Get Card Holder Info
    private static function getCardHolder($document)
    {
        $url = sprintf('%s/acquire/cardHolder/cardHolderInfo/search', self::URL);

        $fields = array(
            'vatNumber' =>  $document
        );

        $body = json_encode($fields);

        $gateway = false;

        $header = self::getHeader($gateway);

        $requestType = self::POST_REQUEST;

        
        $apiRequest = self::apiRequest($url, $body, $header, $requestType);

        if (isset($apiRequest->data->cardHolder->entity->idUUID)) {
            return $apiRequest;
        } else {
            $result = self::createCardHolder($document);

            return $result;
        }

        // return $apiRequest;
    }

    public static function createCardHolder($document)
    {
        $user = User::getUserByDocument($document);

        $url = sprintf('%s/acquire/cardHolder/add', self::URL);

        $userName				= $user->first_name." ".$user->last_name;
        $userDocument			= str_replace(".", "", $user->document);
        
        $operationUUID          = Settings::where('key', 'directpay_working_operation_uuid')->first()->value;

        $fields = array(
            'cardHolder'    =>  (object)array(
                'entity'    =>  (object)array(
                    // 'entityTypeId'      =>  1,
                    'name'              =>  $userName,
                    "namePrefix"        =>  $user->first_name,
                    "phoneCelular"      =>  $user->phone,
                    "phoneHome"         =>  $user->phone,
                    'email'             =>  $user->email,
                    'vatNumber'         =>  $userDocument,
                    "identificationTypeId" =>  0,
                    'birthDate'         =>  $user->birthdate
                ),
                
                'mainAddress'    =>  (object)array(
                    'street'        =>  $user->address,
                    'portNumber'    =>  $user->address_number,
                    'city'          =>  $user->address_city,
                    'state'         =>  $user->state,
                    'zipCode'       =>  $user->zipcode,
                    'country'       =>  $user->country
                ),
                'billingAddress'    =>  (object)array(
                    'street'        =>  $user->address,
                    'portNumber'    =>  $user->address_number,
                    'city'          =>  $user->address_city,
                    'state'         =>  $user->state,
                    'zipCode'       =>  $user->zipcode,
                    'country'       =>  $user->country
                )
            ),
            'operationUUID' =>  $operationUUID
        );
        
        $gateway = false;

        $body = json_encode($fields);

        $header = self::getHeader($gateway);

        $requestType = self::POST_REQUEST;

        $apiRequest = self::apiRequest($url, $body, $header, $requestType);

        return $apiRequest;
    }
    
    // Header Request
    private static function getHeader($gateway, $transactionId = null)
    {
        $requesterId      = Settings::findObjectByKey('directpay_requester_id');
        $requesterToken   = "Znm2YRUct8rEYgghCAq75u/NZlxbnPkCjl6eVkTUj48crOrMyoYTChb9EvdZsOoXz6h6Y46VaBRrAIs5wICTNTiliqezP/boxGVHz7mhh802uZA8sAkusfN/HsNd8kAOVoq2SWObPHI8JPxxOPVbzZMTD7+UsYqoCW70w6JafALsylfvn+mqeHnaFfF8bDQkpskAhP4KCg24zU3OxapJe1klaFkcd5VzpZu4m4bOPGHoo4NncXpJI4Fa2w73gjqtf2PSEW+s/gKMNHw49zROyvqAL9uQu4OAlg3rqYNOLYwtbioxWMHtf78ZONhb2OKfFR";
        $uniqueTrxId      = Settings::findObjectByKey('directpay_unique_trx_id');
        $gatewayId        = Settings::findObjectByKey('directpay_gateway_id');
        
        $header = array (
            'Content-Type: application/json; charset=UTF-8',
            'Accept: application/json',
            'requester-id:'.$requesterId->value, 
            'requester-token:'.$requesterToken,
            'unique-trx-id:'.$uniqueTrxId->value         
        );

        if ($gateway) {
            array_push($header, 'gateway-uuid:'.$gatewayId->value);
        }

        if ($transactionId) {
            array_push($header, $transactionId);
        }

        return $header;
    }

    private static function getSuccessMessage($fields, $result)
    {
        $capture = json_decode($fields);
        if (isset($capture->capture)) {
            if ($capture->capture) {
                if($result->responseMessage == self::STATUS_PROCESSED){
                    return self::STATUS_SUCCEEDED;
                } else {
                    return $result->responseMessage;
                }
            } elseif (!$capture->capture) {
                if($result->responseMessage == self::STATUS_AUTHORIZED){
                    return self::STATUS_SUCCEEDED;
                } else {
                    return $result->responseMessage;
                }
            } 
        } else if ($result->responseCode == 0){

            return self::STATUS_SUCCEEDED; 
        } else {
            if (isset($result->reversals)) {
                return self::STATUS_SUCCEEDED;
            } else {
                return "ERROR";
            }
        }
       
    }

    // Api Request
    public static function apiRequest($url, $fields, $header, $requestType)
    {
        try
        {
            $session = curl_init();

            curl_setopt($session, CURLOPT_URL, $url);
            curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($session, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($session, CURLOPT_CUSTOMREQUEST, $requestType );
            // curl_setopt($session, CURLOPT_POST, true);
            
            if ($fields) {
                curl_setopt($session, CURLOPT_POSTFIELDS, ($fields));
            }	
            curl_setopt($session, CURLOPT_TIMEOUT, self::APP_TIMEOUT);
            curl_setopt($session, CURLOPT_HTTPHEADER, $header);            

            $msg_chk = curl_exec($session);  
            
            $httpcode = curl_getinfo($session, CURLINFO_HTTP_CODE);  

            $result = json_decode($msg_chk);
            $resultMessage = self::getSuccessMessage($fields, $result);
            
            if($httpcode != 200 && $resultMessage != self::STATUS_SUCCEEDED)
                throw new Exception('createCard: '. $result->responseMessage);

            if($resultMessage != self::STATUS_SUCCEEDED)
            {
                throw new Exception(
                    'createCard: '. $result->responseMessage
                );
                \Log::error('Error message: '.$result->responseMessage);
            }
            
            return (object)array (
                'success'           =>  true,
                'data'              =>  $result
            );
        }
        catch(Exception  $ex)
        {
            throw new Exception(
                'createCard: '. $ex->getMessage()
            );
            \Log::error('Error message: '.$ex->getMessage());
            // $return = array(
            //     "success" 					=> false ,
            //     // "transaction_id"            => $result->paymentToken,
            //     "message" 					=> $ex->getMessage()
            // );
            
            // \Log::error(($return));

            // return $return;
        }
    }
}