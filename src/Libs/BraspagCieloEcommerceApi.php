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
use Codificar\PaymentGateways\Libs\handle\phone\PhoneNumber;

class BraspagCieloEcommerceApi
{
    private $apiUrl;
    private $subordinatesUrl;
    private $apiTokenUrl;

    private $prefixAccept = null;

    private $header;

    // const FINGERPRINT_URL   =   "https://h.online-metrix.net/fp/tags.js?";
    // const ORDER_ID          =   "org_id=k8vif92e";
    // const SESSION_ID        =   "&session_id=braspag_split_ilev";

    const NOTIFICATION_URL  =   "https://site.com.br/api/subordinados";

    const CREDIT_CARD   =   "CreditCard";
    const MASTER        =   "Master";
    const VISA          =   "Visa";

    const MCC           =   "5045";

    const INITIAL_INSTALLMENT_NUMBER    =   1;
    const FINAL_INSTALLMENT_NUMBER      =   1;

    const MASTER_PERCENT    =   2.36;
    const VISA_PERCENT      =   2.36;  
    const ROUND_VALUE       =   100;

    const APP_TIMEOUT       =   200;

    public function __construct()
    {
        if(App::environment() == 'production')
        {
            $this->apiUrl           = "https://api.cieloecommerce.cielo.com.br/1/";
            $this->subordinatesUrl  = "https://splitonboarding.braspag.com.br";
            $this->apiTokenUrl      = "https://auth.braspag.com.br/oauth2/token";
            $this->apiGetUrl        = "https://apiquery.cieloecommerce.cielo.com.br/1/";
        }
        else
        {
            $this->apiUrl           = "https://apisandbox.cieloecommerce.cielo.com.br/1";
            $this->subordinatesUrl  = "https://splitonboardingsandbox.braspag.com.br";
            $this->apiTokenUrl      = "https://authsandbox.braspag.com.br/oauth2/token";
            $this->apiGetUrl        = "https://apiquerysandbox.cieloecommerce.cielo.com.br/1/";
            $this->prefixAccept     = " ACCEPT";
        }

        $this->makeToken();
        $this->setHeader();
    }

    public function chargeWithSplit(Payment $payment, Provider $provider = null, $totalAmount, $providerAmount = null, $description, $capture, $split)
    {
        $url = $this->apiUrl."/sales/";

        // if ($provider) {
        //     $ledgerBankAccount = LedgerBankAccount::findBy('provider_id', $provider->id);

        //     $subordinateId = self::checkProviderAccount($ledgerBankAccount);
        // }
        
        //$fingerPrintUrl = sprintf('%s%s%s', self::FINGERPRINT_URL, self::ORDER_ID, self::SESSION_ID . $orderId);

        //$fingerPrint = $this->apiRequest($fingerPrintUrl, null, $header, self::GET_REQUEST);

        $body = $this->getBody($payment, $totalAmount, $capture, null, $provider);

        $apiRequest = $this->apiRequest($url, $body, "POST");

        return $apiRequest;
    }

    public function captureWithSplit(Transaction $transaction, $provider, $totalAmount, $providerAmount)
    {
        $transactionToken = $transaction->gateway_transaction_id;

        $url = sprintf('%s/sales/%s/capture', $this->apiUrl, $transactionToken);

        $fields = $this->getSplitInfo($provider, $transaction->gross_value);

        $body = json_encode($fields);

        $apiRequest = $this->apiRequest($url, $body, "PUT");

        return $apiRequest;
    }

    public function refund(Transaction $transaction)
    {
        $transactionToken = $transaction->gateway_transaction_id;

        $url = sprintf('%s/sales/%s/void', $this->apiUrl, $transactionToken);

        $body = null;

        $apiRequest = $this->apiRequest($url, $body, "PUT");

        return $apiRequest;
    }

    public function retrieve($transaction)
    {
        $transactionToken = $transaction->gateway_transaction_id;

        $url = sprintf('%ssales/%s', $this->apiGetUrl, $transactionToken);

        $body = null;

        $apiRequest = $this->apiRequest($url, $body, "GET");

        return $apiRequest;
    }

    public function createOrUpdateAccount($ledgerBankAccount)
    {
        $url = $this->subordinatesUrl."/api/subordinates/";
        $document = $ledgerBankAccount->document;
        
        $provider = Provider::find($ledgerBankAccount->provider_id);

        $bankCode = Bank::getBankCode($ledgerBankAccount->bank_id);

        $docType = ((strlen($document)) > 11) ? "CNPJ" : "CPF";

        $state = getAbbreviationState($provider->state);

        if ($ledgerBankAccount->account_type == "conta_corrente") {
            $accountType = "CheckingAccount";
        } else {
            $accountType = "SavingsAccount";
        }
        
        $phone = $provider->phone;
        try {
            $phoneLib = new PhoneNumber($provider->phone);
            $phone = $phoneLib->getFullPhoneNumberInt();
        } catch (Exception $e) {
            \Log::error($e->getMessage() . $e->getTraceAsString());
        }

        $fields = array(
            'CorporateName' =>  $ledgerBankAccount->holder,
            'FancyName'     =>  $ledgerBankAccount->holder,
            'DocumentNumber'    =>  $ledgerBankAccount->document,
            'DocumentType'      =>  $docType,
            'MerchantCategoryCode'  =>  self::MCC,
            'ContactName'           =>  $ledgerBankAccount->holder,
            'ContactPhone'          =>  $phone,
            'MailAddress'           =>  $provider->email,
            'Website'               =>  '',
            'BankAccount'           =>  (object)array(
                'Bank'              =>  $bankCode,
                'BankAccountType'   =>  $accountType,
                'Number'            =>  $ledgerBankAccount->account,
                'VerifierDigit'     =>  $ledgerBankAccount->account_digit,
                'AgencyNumber'      =>  $ledgerBankAccount->agency,
                'AgencyDigit'       =>  $ledgerBankAccount->agency_digit,
                'DocumentNumber'    =>  $ledgerBankAccount->document,
                'DocumentType'      =>  $docType
            ),
            'Address'               =>  (object)array(
                'Street'            =>  $provider->address,
                'Number'            =>  $provider->address_number,
                'Complement'        =>  $provider->adress_complements,
                'Neighborhood'      =>  $provider->address_neighbour,
                'City'              =>  $provider->address_city,
                'State'             =>  $state,
                'ZipCode'           =>  $provider->zipcode
            ),
            'Agreement'             =>  (object)array(
                'Fee'               =>  0,
                'MerchantDiscountRates' =>  array(
                    (object)array(
                        'PaymentArrangement'    =>  (object)array(
                            'Product'           =>  self::CREDIT_CARD,
                            'Brand'             =>  self::MASTER
                        ),
                        'InitialInstallmentNumber'  =>  self::INITIAL_INSTALLMENT_NUMBER,
                        'FinalInstallmentNumber'    =>  self::FINAL_INSTALLMENT_NUMBER,
                        'Percent'                   =>  self::MASTER_PERCENT
                    ),
                    (object)array(
                        'PaymentArrangement'    =>  (object)array(
                            'Product'           =>  self::CREDIT_CARD,
                            'Brand'             =>  self::VISA
                        ),
                        'InitialInstallmentNumber'  =>  self::INITIAL_INSTALLMENT_NUMBER,
                        'FinalInstallmentNumber'    =>  self::FINAL_INSTALLMENT_NUMBER,
                        'Percent'                   =>  self::VISA_PERCENT
                    ),
                    
                ) 
            ),
            'Notification'          =>  (object)array(
                'Url'               =>  self::NOTIFICATION_URL,
                'Headers'           =>  array(
                    (object)array(
                        'Key'           =>  'Key1',
                        'Value'         =>  'Value'
                    )
                    
                )
            )
        );

        $body = json_encode($fields);

        $apiRequest = $this->apiRequest($url, $body, "POST");

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

    // public static function checkProviderAccount($ledgerBankAccount)
    // {
    //     $subordinateId = $ledgerBankAccount->recipient_id;

    //     if ($subordinateId == '' || $subordinateId == 'empty') {
    //         $response = self::createOrUpdateAccount($ledgerBankAccount);
            
    //     } else {
    //         $response = self::getBrasPagAccount($subordinateId);
    //     }
        

    //     if ($response->success) {
    //         $result = (object)array(
    //             'success'       => true,
    //             'recipient_id'   => $ledgerBankAccount->recipient_id
    //         );
    //         $ledgerBankAccount->recipient_id = $response->data->MerchantId;
    //         $ledgerBankAccount->save();
    //     } else {
    //         $newAccount = self::createOrUpdateAccount($ledgerBankAccount);
    //         if ($newAccount->success) {
    //             $ledgerBankAccount->recipient_id = $newAccount->data->MerchantId;
    //             $ledgerBankAccount->save();
    //             $result = (object)array(
    //                 'success'       => true,
    //                 'recipient_id'   => $ledgerBankAccount->recipient_id
    //             );
    //         } else {
    //             $result = (object)array(
    //                 'success'       => false,
    //                 'recipient_id'   => ""
    //             );
    //         }
    //     }

    //     return $result;
    // }

    public function getBrasPagAccount($subordinateId)
    {
        $url = sprintf('%s/api/subordinates/%s', $this->subordinatesUrl, $subordinateId);

        $body = null;

        $apiRequest = $this->apiRequest($url, $body, "GET");

        return $apiRequest;
    }

    private function amountRound($amount)
    {
        $amount = $amount * self::ROUND_VALUE;
        $amount = (int)$amount;

        return $amount;
    }

    private function getSplitInfo($provider, $totalAmount)
    {
        $ledgerBankAccount = LedgerBankAccount::findBy('provider_id', $provider->id);

        $totalAmount = $this->amountRound($totalAmount);

        $providerPercentage = Settings::findObjectByKey('provider_amount_for_each_request_in_percentage');
        $percentage = 100 - (int)$providerPercentage->value;

        $fields = array(
            'SplitPayments'     =>  array(
                (object)array(
                    'SubordinateMerchantId'     =>  $ledgerBankAccount->recipient_id,
                    'Amount'                    =>  $totalAmount,
                    'Fares'                     =>  (object)array(
                        'Mdr'           =>  $percentage,
                        'Fee'           =>  0
                    )
                )
            )
        );

        return $fields;
    }

    private function setHeader()
    {
        try
        {
            $brasPagToken   =   Settings::findObjectByKey('braspag_token');
            $this->header   =   array (
                'Content-Type: application/json; charset=UTF-8',
                'Accept: application/json',
                'Authorization: Bearer '.$brasPagToken->value 
            );
        }
        catch(Exception  $ex)
        {
            \Log::error($ex->getMessage());
        }
    }

    private function makeToken()
    {
        $clientId       = Settings::findObjectByKey('braspag_client_id');
        $clientSecret   = Settings::findObjectByKey('braspag_client_secret');

        $concateString = base64_encode($clientId->value.':'.$clientSecret->value);

        $body = "grant_type=client_credentials";
        
        $this->header = array (
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Basic '.$concateString, 
        );

        $apiRequest = $this->apiRequest($this->apiTokenUrl, $body, "POST");

        \Log::info("Token Request:". print_r($apiRequest,1));

        try {
            $token = Settings::findObjectByKey('braspag_token');
            $token->value = $apiRequest->data->access_token;
            $token->save();
        }
        catch (Exception $ex){
            \Log::error($ex->getMessage().$ex->getTraceAsString());
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

            if (isset($fields))
            {
                $indexHeader = array_search('Content-Length: 0', $this->header);
                if($indexHeader !== false){
                    unset($this->header[$indexHeader]);
                }

                curl_setopt($session, CURLOPT_POSTFIELDS, ($fields));
            }
            else
            {
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
        //         "Provider" => Settings::getBilletProvider(),
        //         "Type" => "Boleto",
        //         "Amount" => $this->amountRound($amount),
        //         "ExpirationDate" => date('Y-m-d', strtotime($boletoExpirationDate)),
        //         "Instructions" => $boletoInstructions
        //     ]
        // ];

        // $body = json_encode($fields);

        // $apiRequest = $this->apiRequest($url, $body, "POST");
        // return $apiRequest;

        //
        return (object)array(
            "success" 					=> false ,
            "message" 					=> 'billet_fail'
        );
    }

    private function getBody($payment, $amount, $capture = false, $user = null, $provider = null)
    {
        $expirationDate = $this->getExpirationDate($payment);

        $user = User::find($payment->user_id);

        $cardNumber = $payment->getCardNumber();

        $phone = $user->phone;
        try {
            $phoneLib = new PhoneNumber($user->phone);
            $phone = $phoneLib->getFullPhoneNumberInt();
        } catch (Exception $e) {
            \Log::error($e->getMessage() . $e->getTraceAsString());
        }

        $docType = ((strlen($user->document)) > 11) ? "CNPJ" : "CPF";

        $orderId = $this->getOrderId();

        $totalAmount = $this->amountRound($amount);

        $softDescriptor = strlen(Settings::findByKey('website_title')) >= 13 ? substr(Settings::findByKey('website_title'),0,12)."." : Settings::findByKey('website_title');

        $fields = array
        (
            'MerchantOrderId'   =>  $orderId,
            'Customer'          =>  (object)array(
                'Name'          =>  $user->first_name.' '.$user->last_name.$this->prefixAccept,
                "email"         =>  $user->email,
                "Identity"      =>  $user->document,
                "identitytype"  =>  $docType,
                "Mobile"        =>  $phone,
                "Phone"         =>  $phone,
            ),
            'Payment'           =>  (object)array(
                // 'Provider'      =>  'Simulado',
                'Type'          =>  'SplittedCreditCard',
                'Amount'        =>  $totalAmount,
                'Installments'  =>  1,
                'Capture'       =>  $capture,
                'SoftDescriptor'    =>  $softDescriptor,
                'CreditCard'            =>  (object)array(
                    'CardNumber'        =>  $cardNumber,
                    'Holder'            =>  $payment->getCardHolder(),
                    'ExpirationDate'    =>  $expirationDate,
                    'SecurityCode'      =>  $payment->getCardCvc(),
                    'Brand'             =>  $this->getBrand($cardNumber),
                    'SaveCard'          =>  false
                ),
                'fraudanalysis'     =>  (object)array(
                    'provider'      =>  'cybersource',
                    'Shipping'      =>  (object)array(
                        'Addressee' =>  $user->first_name.' '.$user->last_name.$this->prefixAccept
                    ),
                    'browser'       =>  (object)array(
                        'ipaddress'             =>  getIp(),
                        'browserfingerprint'    =>  $orderId
                    ),
                    'totalorderamount'          =>  $totalAmount,
                    'MerchantDefinedFields'     =>  array(
                        (object)array(
                            'id'    =>  1,
                            'value' =>  'Guest'
                        )
                    )
                )
            )
        );

        if ($capture && $provider) {
            $split = $this->getSplitInfo($provider, $totalAmount);
            $fields['SplitPayments'] = $split['SplitPayments'];
        }
        
        return json_encode($fields);
    }

    public function getCountrySlug($country)
    {
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