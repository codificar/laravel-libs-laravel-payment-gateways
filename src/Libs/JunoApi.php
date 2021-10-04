<?php

namespace Codificar\PaymentGateways\Libs;
use Carbon\Carbon;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7;

use Codificar\PaymentGateways\Libs\CardFlag;

use Settings, Payment, Provider, Bank;


class JunoApi {

    const URL_SANDBOX = 'https://sandbox.boletobancario.com/api-integration/';
    const URL_PROD = 'https://api.juno.com.br/';

    const URL_AUTH_SANDBOX = 'https://sandbox.boletobancario.com/authorization-server/';
    const URL_AUTH_PROD = 'https://api.juno.com.br/authorization-server/';

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
        $auth_exp = Settings::findByKey("juno_auth_token_expiration_date");
        if(!$auth_exp || $auth_exp <= date('Y-m-d H:i:s')) { // se nao tiver data de expiracao ou se ja estiver expirado, gera um novo token
            try {
                $isSandbox = Settings::findByKey('juno_sandbox');
                $guzzleAuth = new Client([
                    'base_uri' => (int)$isSandbox ? self::URL_AUTH_SANDBOX : self::URL_AUTH_PROD,
                    'timeout'  => 120, // two minutes timeout
                ]);
                $response = $guzzleAuth->request('POST', 'oauth/token', [
                    'auth' => [
                        Settings::findByKey("juno_client_id"), 
                        Settings::findByKey("juno_secret")
                    ],
                    'form_params' => [
                        'grant_type' => "client_credentials"
                    ]
                ]);
                if($response->getStatusCode() == 200 && $response->getBody()) {
                    $res = json_decode($response->getBody());
                    Settings::where('key', 'juno_auth_token')->update(['value' => $res->access_token]);
                    Settings::where('key', 'juno_auth_token_expiration_date')->update(['value' => date('Y-m-d H:i:s', strtotime('1 hour'))]); //validade do token e de uma hora
                } else {
                    return false;
                }
            } catch (RequestException $e) {
                \Log::error(print_r($e->getResponse()->getBody()->getContents(), true));
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
                $response = $this->guzzle->request('POST', 'credit-cards/tokenization', [
                    'headers' => $this->headers, 
                    'json' => [
                        'creditCardHash' => $creditCardHash
                    ]
                ]);
                if($response->getStatusCode() == 200) {
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

    public function billetCharge($client, $amount, $billetInstructions,  $billetExpirationDate) {
        try {
            $headersOk = $this->setHeaders();
            if($headersOk) {
                $datetime = new Carbon($billetExpirationDate);
                $exp = $datetime->format('Y-m-d');
                $response = $this->guzzle->request('POST', 'charges', [
                    'headers' => $this->headers, 
                    'json' => [
                        'charge' => array(
                            "description" => $billetInstructions ? $billetInstructions : "Cobrança por prestação de serviço",
                            "amount" => $amount,
                            "dueDate" => $exp,
                            "paymentTypes" => ["BOLETO"]
                        ),
                        'billing' => $this->getCustomer($client)
                    ]
                ]);
                if($response->getStatusCode() == 200) {
                    $res = json_decode($response->getBody());
                    return $res;
                } 
                else {
                    return null;
                }
            }
            
        } catch (RequestException $e) {
            \Log::error("Juno create billet charge error.");
            \Log::error(print_r($e->getResponse()->getBody()->getContents(), true));
            return null;
        }
    }

    /**
     * A cobranca no cartao da juno passa por duas etapas:
     * 1º: e criado uma cobranca generica, com os dados do usuario. O pagamento nao e realizado ainda, portanto o status fica "pendente". Rota: "/charges"
     * 2º: e realizado o pagamento da cobranca gerada anteriormente com o cartao. Rota: "payments"
     */
    public function charge(Payment $payment, $amount, $description, $capture) {
        try {
            $headersOk = $this->setHeaders();
            if($headersOk) {
                $holder = $payment->user_id ? $payment->User : $payment->Provider;
                $response = $this->guzzle->request('POST', 'charges', [
                    'headers' => $this->headers, 
                    'json' => [
                        'charge' => array(
                            "description" => $description ? $description : "Cobrança por prestação de serviço",
                            "amount" => $amount,
                            "installments" => 1,
                            "paymentTypes" => ["CREDIT_CARD"]
                        ),
                        'billing' => $this->getCustomer($holder)
                    ]
                ]);
                if($response->getStatusCode() == 200) {
                    //agora que a primeira etapa (criar cobranca) deu certo, vamos realizar o pagamento com cartao
                    $res = json_decode($response->getBody());
                    return $this->doPaymentCard($payment, $capture, $res->_embedded->charges[0]->id);
                } 
                else {
                    return null;
                }
            }
            
        } catch (RequestException $e) {
            \Log::error("Juno create charge error.");
            \Log::error(print_r($e->getResponse()->getBody()->getContents(), true));
            return null;
        }
    }

    /**
     * cancel charge - cancela apenas cobrancas que ainda nao foram realizados o pagamento
     */
    public function cancelCharge($chargeId) {
        try {
            $headersOk = $this->setHeaders();
            if($headersOk) {
                $response = $this->guzzle->request('PUT', 'charges/' . $chargeId . '/cancelation', [
                    'headers' => $this->headers
                ]);
                if($response->getStatusCode() == 204 || $response->getStatusCode() == 200) {
                    return true;
                } 
                else {
                    return false;
                }
            }
        } catch (RequestException $e) {
            \Log::error("Juno cancel charge error");
            \Log::error(print_r($e->getResponse()->getBody()->getContents(), true));
            return false;
        }
    }

    private function doPaymentCard(Payment $payment, $capture, $chargeId) {
        try {
            $headersOk = $this->setHeaders();
            if($headersOk) {
                $holder = $payment->user_id ? $payment->User : $payment->Provider;
                $response = $this->guzzle->request('POST', 'payments', [
                    'headers' => $this->headers, 
                    'json' => [
                        'chargeId' => $chargeId,
                        'billing' => array(
                            'email' => $holder->email,
                            'address' => $this->getCustomerAddress($holder),
                            'delayed' => $capture ? false : true
                        ),
                        'creditCardDetails' => array(
                            'creditCardId' => $payment->card_token
                        )
                    ]
                ]);
                if($response->getStatusCode() == 200) {
                    return json_decode($response->getBody());
                } else {
                    // se deu erro com cartao, entao cancela cobranca
                    $this->cancelCharge($chargeId);
                    return null;
                }
            }
            
        } catch (RequestException $e) {
            // se deu erro com cartao, entao cancela cobranca
            $this->cancelCharge($chargeId);
            
            \Log::error("Erro ao cobrar no cartao de credito");
            \Log::error(print_r($e->getResponse()->getBody()->getContents(), true));
            return null;
        }
    }

    // payIsConfirmed: se o pagamento ja foi confirmado (ou seja, se ja foi capturado)
    public function refundCard($chargeId, $payId, $payIsConfirmed) {
        //se o pagamento nao foi confirmado, entao deve ser feito o cancelamento da cobranca, nao do pagamento
        if(!$payIsConfirmed) {
            try {
                $cancel = $this->cancelCharge($chargeId);
                return $cancel;
            } catch (Exception $e) {
                return false;
            }
        } else {
            try {
                $headersOk = $this->setHeaders();
                if($headersOk) {
                    $response = $this->guzzle->request('POST', 'payments/' . $payId . '/refunds', [
                        'headers' => $this->headers
                    ]);
                    if($response->getStatusCode() == 200) {
                        return true;
                    } 
                    else {
                        return false;
                    }
                }
                
            } catch (RequestException $e) {
                \Log::error("Juno refund error");
                \Log::error(print_r($e->getResponse()->getBody()->getContents(), true));
                return false;
            }
        }
    }


    public function capturePaymentCard($payId, $amount) {
        try {
            $headersOk = $this->setHeaders();
            if($headersOk) {
                $response = $this->guzzle->request('POST', 'payments/' . $payId . '/capture', [
                    'headers' => $this->headers, 
                    'json' => [
                        'amount' => $amount
                    ]
                ]);
                if($response->getStatusCode() == 200) {
                    return true;
                } 
                else {
                    return false;
                }
            }
            
        } catch (RequestException $e) {
            \Log::error("Juno capture error");
            \Log::error(print_r($e->getResponse()->getBody()->getContents(), true));
            return false;
        }
    }

    public function retrieve($chargeId) {
        try {
            $headersOk = $this->setHeaders();
            if($headersOk) {
                $response = $this->guzzle->request('GET', 'charges/' . $chargeId, [
                    'headers' => $this->headers
                ]);
                if($response->getStatusCode() == 200) {
                    return json_decode($response->getBody());
                } 
                else {
                    return false;
                }
            }
            
        } catch (RequestException $e) {
            \Log::error("retrieve juno error");
            \Log::error(print_r($e->getResponse()->getBody()->getContents(), true));
            return false;
        }
    }

    public function createDigitalAccount($ledgerBankAccount) {
        try {
            $headersOk = $this->setHeaders();
            if($headersOk) {
                $provider = Provider::find($ledgerBankAccount->provider_id);
                $bank = Bank::where('id', $ledgerBankAccount->bank_id)->first();
                $response = $this->guzzle->request('POST', 'digital-accounts', [
                    'headers' => $this->headers, 
                    'json' => [
                        'type' => 'PAYMENT',
                        'name' => $ledgerBankAccount->holder,
                        'document' => preg_replace( '/[^0-9]/', '', $ledgerBankAccount->document),
                        'email' => $provider->email,
                        'birthDate' => (new Carbon($ledgerBankAccount->birthday_date))->format('Y-m-d'),
                        'phone' => $provider->phone,
                        'businessArea' => 2033, //2033 - cod da juno para todo tipo de 'servicos'
                        'linesOfBusiness' => 'Prestação de Serviço',
                        'address' => $this->getCustomerAddress($provider),
                        'bankAccount' => array(
                            'bankNumber' => $bank->code,
                            'agencyNumber' => $ledgerBankAccount->agency,
                            'accountNumber' => $ledgerBankAccount->account,
                            'accountType' => $ledgerBankAccount->account_type == 'conta_poupanca' ? 'SAVINGS' : 'CHECKING',
                            'accountHolder' => array(
                                'name' => $ledgerBankAccount->holder,
                                'document' => preg_replace( '/[^0-9]/', '', $ledgerBankAccount->document)
                            ),
                        ),
                        'autoTransfer' => true
                    ]
                ]);
                if($response->getStatusCode() == 200) {
                    return json_decode($response->getBody());
                } 
                else {
                    return null;
                }
            }
            
        } catch (RequestException $e) {
            \Log::error("Juno create account error.");
            \Log::error(print_r($e->getResponse()->getBody()->getContents(), true));
            return null;
        }
    }

    private function getCustomer($holder){
		$customer = array(
            "name" => $holder->getFullName(),
            "document" => $this->cleanWord($holder->document),
            "email" => $holder->email,
            "address" => $this->getCustomerAddress($holder),
            "notify" => false
		);

		return $customer ;
	}

    private function getCustomerAddress($holder) {
        return array (
            "street" => $holder->getStreet(),
            "number" => $holder->getStreetNumber(),
            "neighborhood" => $holder->getNeighborhood(),
            "city" => $holder->address_city,
            "state" => $holder->state,
            "postCode" => $this->cleanWord($holder->getZipcode())
        );
    }

    public function cleanWord($word)
	{
		$word = str_replace(".", "", $word);
		$word = str_replace("-", "", $word);
		$word = str_replace("/", "", $word);
		$word = str_replace("/n", "", $word);

		return $word;
	}
    
}