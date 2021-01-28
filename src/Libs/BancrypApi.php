<?php

namespace Codificar\PaymentGateways\Libs;

use Payment;
use Provider;
use Transaction;
use User;
use LedgerBankAccount;
use Settings;

use Carbon\Carbon;

class BancrypApi
{

    const URL_SAND = "https://service.bancryp.info/api";
    const QRCODE_URL_SAND = "https://sandbox.bancryp.info/qrcode/qr-code?";  

    const URL  = "https://api.bancryp.com/api";
    const QRCODE_URL = "https://api.bancryp.com/qrcode/qr-code?";

    const CLIENT_VERSION = "1.0.0.0";

    const BITCOIN_COIN = "BTC";

    const POST_REQUEST      = 'POST';
    const GET_REQUEST       = 'GET';
    const PUT_REQUEST       = 'PUT';

    const APP_TIMEOUT = 200;

    
    public static function charge($amount){

        $time = Carbon::now()->toDateTimeString();

        $sequenceId = self::getSequenceId($time);

        $url = sprintf('%s/payment/start', self::getUrl());

        $apiKey = Settings::findObjectByKey('bancryp_api_key');
        $secretKey = Settings::findObjectByKey('bancryp_secret_key');

        $fields = array (
            'sequence_id'       =>  $sequenceId,
            'coin'              =>  self::BITCOIN_COIN,
            'value_brl'         =>  $amount,
            'apikey'            =>  ($apiKey ? $apiKey->value : null),
            'secret'            =>  ($secretKey ? $secretKey->value : null),
            'client_version'    =>  self::CLIENT_VERSION

        );

        $body = json_encode($fields);

        $header = self::getHeader($apiKey , $secretKey);

        $requestType = self::POST_REQUEST;

        $apiRequest = self::apiRequest($url, $body, $header, $requestType);

        return $apiRequest;
    }

    public static function capture(Transaction $transaction)
    {
        $transactionToken = $transaction->gateway_transaction_id;

        $url = sprintf('%s/payment/detail', self::getUrl(), $transactionToken);

        $apiKey = Settings::findObjectByKey('bancryp_api_key');
        $secretKey = Settings::findObjectByKey('bancryp_secret_key');

        $fields = array (
            'payment_id'            =>  $transactionToken,
            'apikey'                =>  ($apiKey ? $apiKey->value : null),
            'secret'                =>  ($secretKey ? $secretKey->value : null),
            'client_version'        =>  self::CLIENT_VERSION
        );

        $body = json_encode($fields);

        $header = self::getHeader($apiKey , $secretKey);

        $requestType = self::POST_REQUEST;

        $apiRequest = self::apiRequest($url, $body, $header, $requestType);

        return $apiRequest;
    }


    //Funções Helper

    //Função para criar numeração unica da transação
    private static function getSequenceId($time)
    {
        $value = str_replace(' ', '', $time);
        $value = str_replace('-', '', $value);
        $value = str_replace(':', '', $value);

        return $value;
    }

    private static function getUrl()
    {
    
        if (App::environment() == 'production')
            return self::URL;
        
        return self::URL_SAND;
    }

    private static function getQRCodeUrl()
    {
    
        if (App::environment() == 'production')
            return self::QRCODE_URL;
        
        return self::QRCODE_URL_SAND;
    }

    private static function getHeader($apiKey = null, $secretKey = null)
    {
        $token = null ;
        if($apiKey && $apiKey->value && $secretKey && $secretKey->value){
            $token = base64_encode($apiKey->value.':'.$secretKey->value);
        }
        elseif($apiKey && $apiKey->value) {
            $token = $apiKey->value ;
        }

        $header = array (
            'Content-Type: application/json;',
            'Accept: */*',     
            'Authorization: Bearer '.$token
        );


        return $header;
    }
    

    //Função para requisição Curl
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
                // curl_setopt($session, CURLOPT_POSTFIELDS, json_encode(array()));
            }

            \Log::debug("Bancryp url". print_r($url,1));

            \Log::debug("Bancryp fields". print_r($fields,1));

            \Log::debug("Bancryp header". print_r($header,1));

            curl_setopt($session, CURLOPT_TIMEOUT, self::APP_TIMEOUT);
            curl_setopt($session, CURLOPT_HTTPHEADER, $header);            

            $msg_chk = curl_exec($session); 
            
            \Log::debug("Bancryp return". $msg_chk);
            
            $httpcode = curl_getinfo($session, CURLINFO_HTTP_CODE);  

            $result = json_decode($msg_chk);
            // $resultMessage = self::getrequestMessage($result);

            if ($httpcode == 200) {

                $value = sprintf('%f', $result->coin_value);

                $url = sprintf('%scoin_addr=%s&amount=%s&quote_id=%s', self::getQRCodeUrl(), $result->coin_addr, $value, $result->payment_id);

                return (object)array (
                    'success'           =>  true,
                    'data'              =>  $result,
                    'url'               =>  $result->qrcode_url
                );
            } else {

                $return = array(
                    "success" 					=> false ,
                    'data'                      => null ,
                    "message" 					=> isset($result->message) ? $result->message : print_r($result,1)
                );

                
                \Log::error('Error message: '.print_r($result,1));
            }            

        }
        catch(Exception  $ex)
        {
            $return = array(
                "success" 					=> false ,
                'data'                      => null ,
                // "transaction_id"            => $result->paymentToken,
                "message" 					=> $ex->getMessage()
            );
            
            \Log::error(print_r($return,1).$ex->getMessage().$ex->getTraceAsString());

            return $return;
        }
    }
}