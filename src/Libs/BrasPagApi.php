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

class BraspagApi
{
    private $apiUrl;

    private $prefixAccept = null;

    private $header;

    private $braspagProvider;

    const NOTIFICATION_URL  =   "https://site.com.br/api/subordinados";

    const CREDIT_CARD   =   "CreditCard";
    const DEBIT_CARD    =   "DebitCard";

    const MASTER        =   "Master";
    const VISA          =   "Visa";

    const FEE           =   0;

    const ROUND_VALUE       =   100;

    const APP_TIMEOUT       =   200;

    public function __construct()
    {
        if(App::environment() == 'production')
        {
            $this->apiUrl           = "https://api.braspag.com.br/v2/";
            $this->apiGetUrl        = "https://apiquery.braspag.com.br/v2/";
            $this->braspagProvider  = "Cielo30";
        }
        else
        {
            $this->apiUrl           = "https://apisandbox.braspag.com.br/v2/";
            $this->apiGetUrl        = "https://apiquerysandbox.braspag.com.br/v2/";
            $this->prefixAccept     = " ACCEPT";
            $this->braspagProvider  = "Simulado";
        }

        $this->setHeader();
    }

    public function charge($payment, $user, $amount, $capture, $cardType, $description)
    {
        $url = $this->apiUrl."sales/";

        $body = $this->getBody($payment, $amount, $capture, $cardType, $user);

        $apiRequest = $this->apiRequest($url, $body, "POST");

        return $apiRequest;
    }

    public function capture(Transaction $transaction)
    {
        $url = sprintf('%ssales/%s/capture', $this->apiUrl, $transaction->gateway_transaction_id);

        $body = null;

        $apiRequest = $this->apiRequest($url, $body, "PUT");

        return $apiRequest;
    }

    public function refund(Transaction $transaction)
    {
        $url = sprintf('%ssales/%s/void', $this->apiUrl, $transaction->gateway_transaction_id);

        $body = null;

        $apiRequest = $this->apiRequest($url, $body, "PUT");

        return $apiRequest;
    }

    public function retrieve($transaction)
    {
        $url = sprintf('%ssales/%s', $this->apiGetUrl, $transaction->gateway_transaction_id);

        $body = null;

        $apiRequest = $this->apiRequest($url, $body, "GET");

        return $apiRequest;
    }

    private function getOrderId()
    {
        list($microSeconds, $seconds) = explode(" ", microtime());
        $orderId = $seconds.substr($microSeconds,2,-3);

        return $orderId;
    }

    private function getExpirationDate($payment)
    {
        $month = $payment->getCardExpirationMonth();
        $year = $payment->getCardExpirationYear();

        $retMonth = (strlen($month) < 2) ? '0'.$month : $month ;
        $retYear = (strlen($year) < 4) ? $year += 2000 : $year ;
        $expDate = trim(sprintf("%s/%s", $retMonth, $retYear));
        
        return $expDate;
    }

    private function getBrand($cardNumber)
    {
        $brand = detectCardType($cardNumber);
        $brand = strtolower($brand) == "mastercard" ? self::MASTER : ucfirst($brand);

        return $brand;
    }

    private function setHeader()
    {
        try
        {
            $merchantId     =   Settings::findObjectByKey('braspag_merchant_id');
            $merchantKey    =   Settings::findObjectByKey('braspag_merchant_key');

            $this->header   =   array (
                'Content-Type: application/json',
                'MerchantId: '.$merchantId->value,
                'MerchantKey: '.$merchantKey->value
            );
        }
        catch(Exception  $ex)
        {
            \Log::error($ex->getMessage());
        }
    }

    public function apiRequest($url, $fields, $requestType)
    {
        try
        {
            $session = curl_init();

            Log::debug('url:'.$url);

            curl_setopt($session, CURLOPT_URL, $url);
            curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($session, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($session, CURLOPT_CUSTOMREQUEST, $requestType );

            $indexHeader = array_search('Content-Length: 0', $this->header);
            if($indexHeader !== false){
                unset($this->header[$indexHeader]);
            }

            if ($fields) {
                curl_setopt($session, CURLOPT_POSTFIELDS, ($fields));
            }   else {
                array_push($this->header, 'Content-Length: 0');
            }

            \Log::debug('fields:'.print_r($fields,1));
            \Log::debug("header:".print_r($this->header,1));
            
            curl_setopt($session, CURLOPT_TIMEOUT, self::APP_TIMEOUT);
            curl_setopt($session, CURLOPT_HTTPHEADER, $this->header);            

            $msg_chk = curl_exec($session);  

            Log::debug('msg_chk'.$msg_chk);
            
            $httpcode = curl_getinfo($session, CURLINFO_HTTP_CODE);  

            $result = json_decode($msg_chk);
            \Log::debug("result_braspag: ".print_r($result,1));
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
                "message" 					=> $ex->getMessage()
            );
            
            \Log::error(($return->message));

            return $return;
        }
    }

    /**
	 * Função para gerar boletos de pagamentos
	 * @param int $amount valor do boleto
	 * @param User/Provider $client instância do usuário ou prestador
	 * @param string $postbackUrl url para receber notificações do status do pagamento
	 * @param string $boletoExpirationDate data de expiração do boleto
	 * @param string $boletoInstructions descrição no boleto
	 * @return array
	 */
    public function billetCharge ($amount, $client, $postbackUrl, $boletoExpirationDate, $boletoInstructions)
    {
        // $url = sprintf('%ssales/', $this->apiUrl);

        // $fields = [
        //     "MerchantOrderId" => $this->getOrderId(),
        //     "Customer" => [
        //         "Name" => $client->getFullName(),
        //         "Identity" => $client->getDocument(),
        //         "IdentityType" => "CPF",
        //         "Address" =>  [  
        //             "Street" => $client->getStreet(),
        //             "Number" => $client->getStreetNumber(),
        //             "Complement" => $client->address_complements,
        //             "ZipCode" => $client->zipcode,
        //             "City" => $client->address_city,
        //             "State" => $client->state,
        //             "Country" => $this->getCountrySlug($client->country),
        //             "District" => $client->getNeighborhood()
        //         ]
        //     ],
        //     "Payment" => [
        //         "Provider" => "Simulado",
        //         "Type" => "Boleto",
        //         "Amount" => $amount,
        //         "ExpirationDate" => date('Y-m-d', strtotime($boletoExpirationDate)),
        //         "Instructions" => $boletoInstructions
        //     ]
        // ];

        // $body = json_encode($fields);

        // $apiRequest = $this->apiRequest($url, $body, "POST");

        // return $apiRequest;
        $return = (object)array(
            "success" 					=> false ,
            "message" 					=> 'not_implemented'
        );
    }

    private function getBody($payment, $amount, $capture = false, $cardType, $user = null)
    {
        $expirationDate = $this->getExpirationDate($payment);

        if(!$user)
            $user = User::find($payment->user_id);

        $cardNumber = $payment->getCardNumber();

        $phone = preg_replace('/[^0-9]/', '', $user->phone);

        $docType = ((strlen($user->document)) > 11) ? "CNPJ" : "CPF";

        $orderId = $this->getOrderId();

        $totalAmount = $amount;

        $softDescriptor = strlen(Settings::findByKey('website_title')) >= 13 ? substr(Settings::findByKey('website_title'),0,12)."." : Settings::findByKey('website_title');

        $fields = array(
            "MerchantOrderId"  =>  $orderId,
            "Customer"  => (object)array(
                'Name'          =>  $user->first_name.' '.$user->last_name.$this->prefixAccept,
                "Email"         =>  $user->email,
                "Identity"      =>  $user->document,
                "Identitytype"  =>  $docType,
                "Mobile"        =>  $phone,
                "Phone"         =>  $phone,
            ),
            "Payment"   => (object)array(
                "Provider"       =>  $this->braspagProvider,
                "Type"           =>  $cardType,
                "Amount"         =>  $totalAmount,
                "Currency"       =>  Settings::findByKey('generic_keywords_currency'),
                "Country"        =>  $this->getCountrySlug($user->country),
                "Installments"   =>  1,
                "Capture"        =>  $capture,
                "SoftDescriptor" =>  $softDescriptor,
                $cardType        =>  (object)array(
                   "CardNumber"      =>  $cardNumber,
                   "Holder"          =>  $payment->getCardHolder(),
                   "ExpirationDate"  =>  $expirationDate,
                   "SecurityCode"    =>  $payment->getCardCvc(),
                   "Brand"           =>  $this->getBrand($cardNumber),
                   "SaveCard"        =>  false
                ),
                "Credentials" => (object)array()
            )
        );
        if(!isset($this->prefixAccept))
            unset($fields["Payment"]->Provider);

        return json_encode($fields);
    }

    public function getBrasPagFee()
    {
        return self::FEE;
    }

    public function getCountrySlug($country){
        switch(strtolower($country))
        {
            case 'angola':
                return 'ago';
            case 'espanha':
            case 'spain':
            case 'paraguai':
            case 'paraguay':
                return 'esp';
            case 'united states':
            case 'estados unidos':
                return 'usa';
            default:
                return 'bra';
        }
    }
}
