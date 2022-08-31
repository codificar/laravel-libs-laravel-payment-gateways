<?php

namespace Codificar\PaymentGateways\Libs;
use Carbon\Carbon;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7;

use Codificar\PaymentGateways\Libs\CardFlag;


class PagarapidoApi {

    const URL_SANDBOX = 'https://api-pagarapido.aquisi.dev.br';
    const URL_PROD = 'https://ecommerce-hml.pagarapido.com.br';

    private $token;
    private $gatewayKey;
    private $production;
    private $guzzle;

    public function __construct($gatewayKey, $production) {
        $this->gatewayKey = $gatewayKey;
        $this->production = $production;
        
        $this->guzzle = new Client([
            'base_uri' => $production ? self::URL_PROD : self::URL_SANDBOX,
            'timeout'  => 120, // two minutes timeout
        ]);
    }

    public function setToken($token) {
        return $this->token = $token;
    }

    public function getToken(){
        return $this->token;
    }

    private function returnError($e = null) {
        return array(
            "success" => false,
            "error" => ($e && $e->hasResponse()) ? Psr7\Message::toString($e->getResponse()) : null
        );
       
    }

    public function login($email, $password) {
        try {
            $response =  $this->guzzle->request('POST', '/signin', [
                'form_params' => [
                    'email' => $email,
                    'password' => $password
                ]
            ]);
            if($response->getStatusCode() == 200) {
                return array(
                    "success" => true,
                    "data" => json_decode($response->getBody())
                );
            } else {
                return $this->returnError();
            }
        } catch (RequestException $e) {
            return $this->returnError($e);
        }
    }

    public function transactionCard($params = []) {
        $year = $params['cardExpirationYear'];
        $month = $params['cardExpirationMonth'];
        $retMonth = (strlen($month) < 2) ? '0'.$month : $month;
        $retYear = (strlen($year) == 4) ? $year[2] . '' . $year[3] : $year;
        
        
        
        if(isset($params['customer']['addresses']['state']) && $params['customer']['addresses']['state']) {
            $stateId = $this->getStateId($params['customer']['addresses']['state']);
        } else {
            $stateId = "31"; //default
        }

        if(isset($params['customer']['addresses']['city']) && $params['customer']['addresses']['city']) {
            $cityId = $this->getCityId($stateId, $params['customer']['addresses']['city']);
        } else {
            $cityId = "3106200"; //default
        }

        $formparams = array(
            'installments' => $params['installments'] ? $params['installments'] : 1,
            'cardNumber' => $params['cardNumber'],
            'cardCvv' => $params['cardCvv'],
            'cardExpirationDate' => $retMonth . $retYear,
            'cardHolderName' => $params['cardHolderName'],
            'cardFlag' => CardFlag::getBrandByCardNumber($params['cardNumber']),
            'paymentType' => "credit_card",
            'gatewayKey' => $this->gatewayKey,
            'returnUrl' => "",
            'foreignOrderId' => "1235964645", // ???
            'amount' => $params['amount'],
            'customer' => [
                'name' => $params['customer']['name'],
                'document' => $params['customer']['document'],
                'type' => $params['customer']['type'],
                'email' => $params['customer']['email'],
                'phoneNumbers' => [ $params['customer']['phone'] ],
                'addresses' => [
                    'billing' => [
                        'city' => $cityId,
                        'neighborhood' => $params['customer']['addresses']['neighborhood'],
                        'number' => $params['customer']['addresses']['number'],
                        'postalCode' => $params['customer']['addresses']['postalCode'],
                        'state' => $stateId,
                        'street' => $params['customer']['addresses']['street']
                    ],
                    'shipping' => [
                        'city' => $cityId,
                        'neighborhood' => $params['customer']['addresses']['neighborhood'],
                        'number' => $params['customer']['addresses']['number'],
                        'postalCode' => $params['customer']['addresses']['postalCode'],
                        'state' => $stateId,
                        'street' => $params['customer']['addresses']['street']
                    ]
                ]
            ]
        );
        try {
            $response =  $this->guzzle->request('POST', '/transaction', [
                'form_params' => $formparams
            ]);
            if($response->getStatusCode() == 201) {
                return array(
                    "success" => true,
                    "data" => json_decode($response->getBody())
                );
            } else {
                return $this->returnError();
            }
        } catch (RequestException $e) {
            return $this->returnError($e);
        }
    }

    public function transactionBoleto($params = []) {

        if(isset($params['customer']['addresses']['state']) && $params['customer']['addresses']['state']) {
            $stateId = $this->getStateId($params['customer']['addresses']['state']);
        } else {
            $stateId = "31"; //default
        }

        if(isset($params['customer']['addresses']['city']) && $params['customer']['addresses']['city']) {
            $cityId = $this->getCityId($stateId, $params['customer']['addresses']['city']);
        } else {
            $cityId = "3106200"; //default
        }

        $formparams = array(
            'paymentType' => "boleto",
            'gatewayKey' => $this->gatewayKey,
            'returnUrl' => $params['returnUrl'],
            'foreignOrderId' => "1235964645", // ???
            'amount' => $params['amount'],
            'customer' => [
                'name' => $params['customer']['name'],
                'document' => $params['customer']['document'],
                'type' => $params['customer']['type'],
                'email' => $params['customer']['email'],
                'phoneNumbers' => [ $params['customer']['phone'] ],
                'addresses' => [
                    'billing' => [
                        'city' => $cityId,
                        'neighborhood' => $params['customer']['addresses']['neighborhood'],
                        'number' => $params['customer']['addresses']['number'],
                        'postalCode' => $params['customer']['addresses']['postalCode'],
                        'state' => $stateId,
                        'street' => $params['customer']['addresses']['street']
                    ],
                    'shipping' => [
                        'city' => $cityId,
                        'neighborhood' => $params['customer']['addresses']['neighborhood'],
                        'number' => $params['customer']['addresses']['number'],
                        'postalCode' => $params['customer']['addresses']['postalCode'],
                        'state' => $stateId,
                        'street' => $params['customer']['addresses']['street']
                    ]
                ]
            ],
            'boletos' => [
                'amount' => $params['amount'],
                'dueDate' => $params['dueDate'],
                'installment' => 1,
                'interest' => [],
                'lateFee' => []
            ]
        );

        try {
            $response =  $this->guzzle->request('POST', '/transaction', [
                'form_params' => $formparams
            ]);
            if($response->getStatusCode() == 201) {
                return array(
                    "success" => true,
                    "data" => json_decode($response->getBody())
                );
            } else {
                return $this->returnError();
            }
        } catch (RequestException $e) {
            return $this->returnError($e);
        }
    }

    public function getTransactionCard($transaction_id) {
        $url = "/transaction" . "/" .  $transaction_id;
        try {
            $response =  $this->guzzle->request('GET', $url, []);
            if($response->getStatusCode() == 200) {
                return array(
                    "success" => true,
                    "data" => json_decode($response->getBody())
                );
            } else {
                return $this->returnError();
            }
        } catch (RequestException $e) {
            return $this->returnError($e);
        }
    }

    public function cancelTransaction($transaction_id) {
        $url = "/transaction" . "/" .  $transaction_id . "/cancel";
        try {
            $headers = [
                'Authorization' => 'Bearer ' . $this->token,        
                'Accept'        => 'application/json',
            ];
            $response =  $this->guzzle->request('PUT', $url, ['headers' => $headers]);
            if($response->getStatusCode() == 200) {
                return array(
                    "success" => true,
                    "data" => json_decode($response->getBody())
                );
            } else {
                return $this->returnError();
            }
        } catch (RequestException $e) {
            return $this->returnError($e);
        }
    }

    private function getStateId($stateName) {
        try {
            $stateName = $this->replaceSpecialChar($stateName);
            $stateName = strtoupper($stateName);
            $response = $this->guzzle->request('GET', "/geo/states", []);
            if($response->getStatusCode() == 200 && $response->getBody() && $response->getBody()) {
                $states = json_decode($response->getBody());
                $stateId = "31";
                foreach($states as $state) {
                    if($state->uf == $stateName || $state->name == $stateName) {
                        $stateId =  $state->_id;
                    }
                }
                return $stateId;
            } else {
                return "31"; //default state id
            }
        } catch (RequestException $e) {
            return "31"; //default state id
        }
    }

    private function getCityId($stateId, $cityName) {
        try {
            $cityName = $this->replaceSpecialChar($cityName); //replace special char
            $cityName = strtoupper($cityName);
            $response = $this->guzzle->request('GET', "geo/states/". $stateId . "/cities", []);
            if($response->getStatusCode() == 200 && $response->getBody() && $response->getBody()) {
                $cities = json_decode($response->getBody());
                $cityId = "3106200";
                foreach($cities as $city) {
                    if($city->name == $cityName) {
                        $cityId =  $city->_id;
                    }
                }
                return $cityId;
            } else {
                return "3106200"; //default city id
            }
        } catch (RequestException $e) {
            return "3106200"; //default city id
        }
    }

    private function replaceSpecialChar($str) {
        $unwanted_array = array('Š'=>'S', 'š'=>'s', 'Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
                            'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U',
                            'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss', 'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c',
                            'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o',
                            'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y' );
        return strtr($str, $unwanted_array);
    }
    
}