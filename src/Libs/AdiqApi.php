<?php

namespace Codificar\PaymentGateways\Libs;
use Carbon\Carbon;

//models do sistema
use Log, Exception;
use App;
use Payment;
use Provider;
use Transaction;
use User;
use LedgerBankAccount;
use Settings;
use Bank;

class AdiqApi
{
    const URL_PROD = "https://ecommerce.adiq.io";

    const URL_DEV = "https://ecommerce-hml.adiq.io";

    const MCC   =   '5045';

    const FEE = 0;
    const CREDIT_CARD = 'Credit';
    const DEDIT_CARD = 'Dedit';

    const INITIAL_INSTALLMENT_NUMBER = 1;
    const FINAL_INSTALLMENT_NUMBER = 1;

    const POST_REQUEST      = 'POST';
    const GET_REQUEST       = 'GET';
    const PUT_REQUEST       = 'PUT';

    const APP_TIMEOUT = 200;

    private static function apiUrl()
    {
        if (App::environment() == 'production')
            return self::URL_PROD;
        
        return self::URL_DEV;
    }

    public static function chargeWithOrNotSplit(Payment $payment, Provider $provider = null, $totalAmount, $providerAmount = null, $description, $capture, $split)
    {
        try {
            $cardExpirationMonth = $payment->getCardExpirationMonth();
            $cardExpirationYear  = $payment->getCardExpirationYear();
            $cardExpirationYear  = $cardExpirationYear % 100;

            $orderId = self::getOrderId();

            $brand = Payment::getBrand($payment);
            $brand = strtolower($brand);

            $url = sprintf('%s/v1/payments/', self::apiUrl());

            $header = self::getHeader();

            $totalAmount = $totalAmount;

            $cardToken = self::tokenizeCard($payment->getCardNumber());

            if(!$cardToken)
                return false;

            $fields = array (
                'sellerInfo'=>  (object)array( 
                    'orderNumber' => $orderId
                ),
                'payment'               =>  (object)array(
                    'transactionType'   =>  self::CREDIT_CARD,
                    'amount'            =>  $totalAmount,
                    'currencyCode'      =>  strtolower(Settings::findByKey('generic_keywords_currency')),
                    'productType'       =>  "Avista",
                    'installments'      =>  1,
                    'captureType'       =>  'pa',
                    'recurrent'         =>  false,
                ),
                'cardInfo'              =>  (object)array(
                    'numberToken'       =>  $cardToken,
                    'cardholderName'    =>  $payment->getCardHolder(),
                    'securityCode'      =>  $payment->getCardCvc(),
                    'brand'             =>  $brand,
                    'expirationMonth'   =>  str_pad($cardExpirationMonth, 2, '0', STR_PAD_LEFT),
                    'expirationYear'    =>  str_pad($cardExpirationYear, 2, '0', STR_PAD_LEFT),
                ),
            );

            $requestType = self::POST_REQUEST;

            $apiRequest = self::apiRequest($url, json_encode($fields), $header, $requestType);

            return $apiRequest;
        } catch(Exception $e) {
            Log:: error($e->getMessage() . $e->getTraceAsString());
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    public static function capture(Transaction $transaction, $amount)
    {
        $transactionToken = $transaction->gateway_transaction_id;

        $url = sprintf('%s/v1/payments/%s/capture', self::apiUrl(), $transactionToken);

        $totalAmount = $amount;

        $body = ['amount' => $totalAmount];

        $header = self::getHeader();

        $requestType = self::PUT_REQUEST;

        $apiRequest = self::apiRequest($url, json_encode($body), $header, $requestType);

        return $apiRequest;
    }

    public static function debit(Payment $payment, Provider $provider = null, $totalAmount, $providerAmount = null, $description)
    {
        $cardExpirationMonth = $payment->getCardExpirationMonth();
        $cardExpirationYear  = $payment->getCardExpirationYear();
        $cardExpirationYear  = $cardExpirationYear % 100;

        $orderId = self::getOrderId();

        $brand = Payment::getBrand($payment);
        $brand = strtolower($brand);

        $url = sprintf('%s/v1/payments/', self::apiUrl());

        $header = self::getHeader();

        $totalAmount = $totalAmount;

        $cardToken = self::tokenizeCard($payment->getCardNumber());

        if(!$cardToken)
            return false;

        $fields = array (
            'sellerInfo'=>  (object)array( 
                'orderNumber' => $orderId
            ),
            'payment'               =>  (object)array(
                'transactionType'   =>  self::DEDIT_CARD,
                'amount'            =>  $totalAmount,
                'currencyCode'      =>  strtolower(Settings::findByKey('generic_keywords_currency')),
                'productType'       =>  "Avista",
                'captureType'       =>  'ac',
                'recurrent'         =>  false,
            ),
            'cardInfo'              =>  (object)array(
                'numberToken'       =>  $cardToken,
                'cardholderName'    =>  $payment->getCardHolder(),
                'securityCode'      =>  $payment->getCardCvc(),
                'brand'             =>  $brand,
                'expirationMonth'   =>  str_pad($cardExpirationMonth, 2, '0', STR_PAD_LEFT),
                'expirationYear'    =>  str_pad($cardExpirationYear, 2, '0', STR_PAD_LEFT),
            ),
        );

        $requestType = self::POST_REQUEST;

        $apiRequest = self::apiRequest($url, json_encode($fields), $header, $requestType);

        return $apiRequest;
    }

    public static function refund(Transaction $transaction)
    {
        $transactionToken = $transaction->gateway_transaction_id;

        $url = sprintf('%s/v1/payments/%s/cancel', self::apiUrl(), $transactionToken);

        $totalAmount = $transaction->gross_value;

        $body = ['amount' => $totalAmount];

        $header = self::getHeader();

        $requestType = self::PUT_REQUEST;

        $apiRequest = self::apiRequest($url, json_encode($body), $header, $requestType);

        return $apiRequest;
    }

    public static function retrieve($transaction)
    {
        $transactionToken = $transaction->gateway_transaction_id;

        $url = sprintf('%s/v1/payments/%s', self::apiUrl(), $transactionToken);

        $body = null;

        $header = self::getHeader();

        $requestType = self::GET_REQUEST;

        $apiRequest = self::apiRequest($url, $body, $header, $requestType);

        return $apiRequest;
    }

    private static function getOrderId()
    {
        list($microSeconds, $seconds) = explode(" ", microtime());
        $orderId = substr($seconds,2).substr($microSeconds,2,-3);

        return $orderId;
    }

    public static function getAdiqFee()
    {
        return self::FEE;
    }

    private static function getHeader()
    {
        $token      =   self::makeToken();
        $adiqToken  =   Settings::findObjectByKey('adiq_token');
        $bearer     =   isset($adiqToken) && isset($adiqToken->value)  ? $adiqToken->value : "";

        $header = array (
            'Content-Type: application/json; charset=UTF-8',
            'Accept: application/json',
            'Authorization: Bearer '.$bearer
        );

        return $header;
    }

    private static function makeToken()
    {
        $clientId       =   Settings::findObjectByKey('adiq_client_id');
        $clientSercret  =   Settings::findObjectByKey('adiq_client_secret');

        $concateString = base64_encode($clientId->value.':'.$clientSercret->value);

        $url = sprintf('%s/auth/oauth2/v1/token', self::apiUrl());

        $body = ['grantType' => 'client_credentials'];
        
        $header = array (
            'Content-Type: application/json-patch+json',
            'Authorization: Basic '.$concateString, 
        );

        $requestType = self::POST_REQUEST;

        $apiRequest = self::apiRequest($url, json_encode($body), $header, $requestType);

        try {
            $token = Settings::findObjectByKey('adiq_token');
            $token->value = $apiRequest->data->accessToken;
            $token->save();
        }
        catch (Exception $ex){
            \Log::error($ex->getMessage().$ex->getTraceAsString());
        }

        return $apiRequest;
    }

    public static function apiRequest($url, $fields, $header, $requestType)
    {
        try
        {
            $session = curl_init();

            curl_setopt($session, CURLOPT_URL, $url);
            curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($session, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($session, CURLOPT_CUSTOMREQUEST, $requestType );

            if ($fields) {
                curl_setopt($session, CURLOPT_POSTFIELDS, ($fields));
            }	else {
                array_push($header, 'Content-Length: 0');
            }
            
            curl_setopt($session, CURLOPT_TIMEOUT, self::APP_TIMEOUT);
            curl_setopt($session, CURLOPT_HTTPHEADER, $header);            

            $msg_chk = curl_exec($session);  
            
            $httpcode = curl_getinfo($session, CURLINFO_HTTP_CODE);  

            $result = json_decode($msg_chk);

            if ($httpcode == 200 ||$httpcode ==  201 ||$httpcode ==  202) {
                return (object)array (
                    'success'           =>  true,
                    'data'              =>  $result
                );
            } else {
                throw new Exception(
                    $msg_chk
                );
                \Log::error('Error message Exception: '.$msg_chk);
            }            

        }
        catch(Exception  $ex)
        {
            $return = (object)array(
                "success" 					=> false ,
                // "transaction_id"            => $result->paymentToken,
                "message" 					=> $ex->getMessage()
            );
            
            \Log::error(($ex));

            return $return;
        }
    }

    private static function tokenizeCard($cardNumber)
    {
        $result = false;
        $url = sprintf('%s/v1/tokens/cards', self::apiUrl());

        $fields = [
            "cardNumber" => $cardNumber
        ];

        $body = json_encode($fields);

        $header = self::getHeader();

        $requestType = self::POST_REQUEST;

        $apiRequest = self::apiRequest($url, $body, $header, $requestType);

        if(isset($apiRequest->data->numberToken))
            $result = $apiRequest->data->numberToken;

        return $result;
    }

}
