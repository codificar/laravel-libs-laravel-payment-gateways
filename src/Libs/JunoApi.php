<?php

namespace Codificar\PaymentGateways\Libs;
use Carbon\Carbon;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7;

use Codificar\PaymentGateways\Libs\CardFlag;

use Settings;


class JunoApi {

    const URL_SANDBOX = 'https://sandbox.boletobancario.com';
    const URL_PROD = 'https://api.juno.com.br';

    private $guzzle;
    private $headers;

    public function __construct() {    
        $isSandbox = Settings::findByKey('juno_sandbox');
        $this->guzzle = new Client([
            'base_uri' => (int)$isSandbox ? self::URL_SANDBOX : self::URL_PROD,
            'timeout'  => 120, // two minutes timeout
        ]);
    }

    //return true if success or false if error
    public function setHeaders() {
        //check if auth token is expired. If yes, generate a new auth token
        if(Settings::findByKey("juno_auth_token_expiration_date")) { // #TODO ajustar verificacao de data de expiracao
            try {
                $response = $this->guzzle->request('POST', '/authorization-server/oauth/token', [
                    'auth' => [
                        Settings::findByKey("juno_client_id"), 
                        Settings::findByKey("juno_secret")
                    ],
                    'form_params' => [
                        'grant_type' => "client_credentials"
                    ]
                ]);
                if($response->getStatusCode() == 200 && $response->getBody() && $response->getBody()->access_token) {
                    Settings::where('key', 'juno_auth_token')->update(['value' => $response->getBody()->access_token]);
                    // #TODO - atualizar data vencimento do auth token
                } else {
                    return false;
                }
            } catch (RequestException $e) {
                return false;
            }
        }

        $this->headers  = [
            'Authorization' => 'Bearer ' . Settings::findByKey("juno_auth_token"),        
            'Content-Type'	=> 'application/json; charset=utf8',
            'Accept'        => 'application/json; charset=utf8',
            'X-Api-Version' => 2,
            'X-Resource-Token' => Settings::findByKey("juno_resource_token")
        ];
        return true;
    }

    public function createCardToken($creditCardHash) {
        try {
            $headersOk = $this->setHeaders();
            if($headersOk) {
                $response = $this->guzzle->request('POST', 'api-integration/credit-cards/tokenization', [
                    'headers' => $this->headers, 
                    'form_params' => [
                        'creditCardHash' => $creditCardHash
                    ]
                ]);
                \Log::debug("resposta");
                \Log::debug(print_r($response, true));
                if($response->getStatusCode() == 200) {
                    \Log::debug(print_r($response->getBody(), true));
                    return json_decode($response->getBody());
                } else {
                    return null;
                }
            }
            
        } catch (RequestException $e) {
            \Log::error("Cartao de credito recusado pela Juno.");
            \Log::error(print_r($e->getResponse()->getBody()->getContents(), true));
            return null;
        }
    }
    
}