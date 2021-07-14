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

class IpagApi
{
    const URL_PROD = "https://api.ipag.com.br/service/";

    const URL_DEV = "https://sandbox.ipag.com.br/service/";

    const MCC   =   '5045';

    const CREDIT_CARD = 'CreditCard';
    const MASTER    ='Master';
    const INITIAL_INSTALLMENT_NUMBER = 1;
    const FINAL_INSTALLMENT_NUMBER = 1;
    const MASTER_PERCENT = 2.36;
    const VISA = "Visa";
    const VISA_PERCENT = 2.36;  

    const ROUND_VALUE = 100;

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

    public static function charge(Payment $payment, $user, $amount, $capture, $cardType)
    {
        $url = sprintf('%s/sales/', self::apiUrl());

        $header = self::getHeader(true);

        $body = self::getBody($payment, $amount, null, $capture, $cardType, $user);

        $apiRequest = self::apiRequest($url, $body, $header, self::POST_REQUEST);

        return $apiRequest;
    }

    public static function chargeWithOrNotSplit(Payment $payment, Provider $provider = null, $amount, $providerAmount = null, $capture)
    {
        $url = sprintf('%s/sales/', self::apiUrl());

        $header = self::getHeader(true);

        $amount = self::amountRound($amount);

        $body = self::getBody($payment, $amount, $providerAmount, $capture, $provider);

        $apiRequest = self::apiRequest($url, $body, $header, self::POST_REQUEST);

        return $apiRequest;
    }

    public static function captureWithSplit(Transaction $transaction, $provider, Payment $payment)
    {
        $url = sprintf('%s/sales/%s/capture', self::apiUrl(), $transaction->gateway_transaction_id);

        $header = self::getHeader(true);

        $fields = self::getBody($payment, $transaction->gross_value, $transaction->provider_value, true, $provider);

        $apiRequest = self::apiRequest($url, $fields, $header, self::POST_REQUEST);

        return $apiRequest;
    }

    public static function capture(Transaction $transaction, $amount)
    {
        $url = sprintf('%s/sales/%s/capture', self::apiUrl(), $transaction->gateway_transaction_id);

        $body = null;

        $header = self::getHeader(true);

        $apiRequest = self::apiRequest($url, $body, $header, self::POST_REQUEST);

        return $apiRequest;
    }

    public static function refund(Transaction $transaction)
    {
        $url = sprintf('%s/sales/%s/void', self::apiUrl(), $transaction->gateway_transaction_id);

        $body = null;

        $header = self::getHeader();

        $apiRequest = self::apiRequest($url, $body, $header, self::POST_REQUEST);

        return $apiRequest;
    }

    public static function retrieve(Transaction $transaction)
    {
        $url = sprintf('%ssales/%s', self::apiUrl(), $transaction->gateway_transaction_id);

        $body = null;

        $header = self::getHeader();

        $apiRequest = self::apiRequest($url, $body, $header, self::GET_REQUEST);

        return $apiRequest;
    }

    public static function createOrUpdateAccount($ledgerBankAccount)
    {
        $url = sprintf('%s/api/subordinates/', self::apiUrl());
        $document = $ledgerBankAccount->document;
        
        $provider = Provider::find($ledgerBankAccount->provider_id);

        $bankCode = Bank::getBankCode($ledgerBankAccount->bank_id);

        $docType = ((strlen($document)) > 11) ? "CNPJ" : "CPF";

        $phone = self::formatPhone($provider->phone);

        $state = self::abbreviationState($provider->state);

        if ($ledgerBankAccount->account_type == "conta_corrente") {
            $accountType = "CheckingAccount";
        } else {
            $accountType = "SavingsAccount";
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

        $header = self::getHeader();

        $apiRequest = self::apiRequest($url, $body, $header, self::POST_REQUEST);

        return $apiRequest;
    }

    private static function getOrderId()
    {
        list($microSeconds, $seconds) = explode(" ", microtime());
        $orderId = $seconds.substr($microSeconds,2,-3);

        return $orderId;
    }

    private static function formatPhone($phone)
    {
        $phone  =   preg_replace('/\D/', '', $phone);
        $phone  =   substr($phone, -11);

        return $phone;
    }

    private static function getExpirationDate($payment)
    {
        $month = $payment->getCardExpirationMonth();
        $year = $payment->getCardExpirationYear();

        $expDate[0] = (strlen($month) < 2) ? '0'.$month : $month ;
        $expDate[1] = (strlen($year) < 4) ? '20'.$year : $year ;

        return $expDate;
    }

    private static function getBrand($payment)
    {
        $brand = Payment::getBrand($payment);
        $brand = strtolower($brand);

        if($brand == 'Master' || $brand == 'master')
            $brand = 'mastercard';

        return $brand;
    }

    public static function checkProviderAccount(LedgerBankAccount $ledgerBankAccount)
    {
        $subordinateId = $ledgerBankAccount->recipient_id;

        if ($subordinateId == '' || $subordinateId == 'empty' || $subordinateId === null) {
            $response = self::createOrUpdateAccount($ledgerBankAccount);
        } else {
            $response = self::getBrasPagAccount($subordinateId);
        }

        if ($response->success) {
            $result = (object)array(
                'success'       => true,
                'recipient_id'   => $ledgerBankAccount->recipient_id
            );
            $ledgerBankAccount->recipient_id = $response->data->MerchantId;
            $ledgerBankAccount->save();
        } else {
            #TODO remover após job de recriar recipients ao trocar gateway
            $newAccount = self::createOrUpdateAccount($ledgerBankAccount);
            if ($newAccount->success) {
                $ledgerBankAccount->recipient_id = $newAccount->data->MerchantId;
                $ledgerBankAccount->save();
                $result = (object)array(
                    'success'       => true,
                    'recipient_id'   => $ledgerBankAccount->recipient_id
                );
            } else {
                $result = (object)array(
                    'success'       => false,
                    'recipient_id'   => ""
                );
            }
        }

        return $result;
    }

    public static function getBrasPagAccount($subordinateId)
    {
        $url = sprintf('%s/api/subordinates/%s', self::apiUrl(), $subordinateId);

        $header = self::getHeader();

        $apiRequest = self::apiRequest($url, $body, $header, self::GET_REQUEST);

        return $apiRequest;
    }

    private static function amountRound($amount)
    {
        $amount = $amount * self::ROUND_VALUE;
        $type = gettype($amount);
        $amount = (int)$amount;

        return $amount;
    }

    private static function getBody($payment, $amount, $providerAmount, $capture = false, $provider = null)
    {
        $expirationDate = self::getExpirationDate($payment);

        $user = User::find($payment->user_id);

        $cardNumber = $payment->getCardNumber();

        $cnpjMask = "%s%s.%s%s%s.%s%s%s/%s%s%s%s-%s%s";
        $cpfMask = "%s%s%s.%s%s%s.%s%s%s-%s%s";

        $mask = ((strlen($user->document)) > 11) ? $cnpjMask : $cpfMask;

        $user->document = vsprintf($mask, str_split($user->document));

        $orderId = self::getOrderId();

        $totalAmount = self::amountRound($amount);

        $fields = array
        (
            'amount'            =>  $totalAmount,
            'order_id'          =>  $orderId,
            'customer'          =>  (object)array(
                'name'          =>  $user->first_name.' '.$user->last_name,
                'cpf_cnpj'      =>  $user->document
            ),
            'payment'           =>  (object)array(
                'type'          =>  'card',
                'capture'       =>  $capture,
                'method'        =>  self::getBrand($cardNumber),
                'installments'  =>  1,
                'card'          =>  (object)array(
                    'holder'        =>  $payment->getCardHolder(),
                    'number'        =>  $cardNumber,
                    'expiry_month'  =>  $expirationDate[0],
                    'expiry_year'   =>  $expirationDate[1],
                    'cvv'           =>  $payment->getCardCvc()
                )
            )
        );

        if($capture && $provider)
        {
            $split = self::getSplitInfo($provider, $providerAmount);
            $fields['split_rules'] = $split['split_rules'];
        }
        
        return json_encode($fields);
    }

    private static function getSplitInfo($providerId, $providerAmount)
    {
        $ledgerBankAccount = LedgerBankAccount::findBy('provider_id', $providerId);

        if(!$ledgerBankAccount)
            return false;

        $subordinateId = self::checkProviderAccount($ledgerBankAccount);

        if(!isset($subordinateId['success']) || (isset($subordinateId['success']) && !$subordinateId['success']))
            return false;

        $providerAmount = self::amountRound($providerAmount);

        $fields = array(
            'split_rules'   =>  array(
                (object)array(
                    'seller_id'             =>  $ledgerBankAccount->recipient_id,
                    'amount'                =>  $providerAmount,
                    'liable'                =>  true,
                    'charge_processing_fee' =>  false
                )
            )
        );

        return $fields;
    }

    private static function getHeader($useVersion = false)
    {
        $version    =   ['x-api-version: 2'];
        $token      =   self::makeToken();
        $ipagToken  =   Settings::findObjectByKey('ipag_token');
        $basic      =   $ipagToken && isset($ipagToken->value)  ? $ipagToken->value : $token;

        $header = array (
            'Content-Type: application/json;',
            'Authorization: Basic '.$basic
        );

        if($useVersion)
            $header = array_merge($header, $version);

        return $header;
    }

    private static function makeToken()
    {
        $ipagId     =   Settings::findObjectByKey('ipag_api_id');
        $ipagKey    =   Settings::findObjectByKey('ipag_api_key');

        $concateString = base64_encode($ipagId->value.':'.$ipagKey->value);

        try {
            $token = Settings::findObjectByKey('ipag_token');
            $token->value = $concateString;
            $token->save();
        }
        catch (Exception $ex){
            \Log::error($ex->getMessage().$ex->getTraceAsString());
        }

        return $concateString;
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
                // curl_setopt($session, CURLOPT_POSTFIELDS, json_encode(array()));
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

    /**
	 * Função para gerar boletos de pagamentos
	 * @param int $amount valor do boleto
	 * @param User/Provider $client instância do usuário ou prestador
	 * @param string $postbackUrl url para receber notificações do status do pagamento
	 * @param string $boletoExpirationDate data de expiração do boleto
	 * @param string $boletoInstructions descrição no boleto
	 * @return array
	 */
    public static function billetCharge ($amount, $client, $postbackUrl, $boletoExpirationDate, $boletoInstructions)
    {
        $url = sprintf('%s/sales/', self::apiUrl());
        $orderId = self::getOrderId();

        $fields = [
            "MerchantOrderId" => $orderId,
            "Customer" => [
                "Name" => $client->getFullName(),
                "Identity" => $client->getDocument(),
                "IdentityType" => "CPF",
                "Address" =>  [  
                    "Street" => $client->getStreet(),
                    "Number" => $client->getStreetNumber(),
                    "Complement" => $client->address_complements,
                    "ZipCode" => $client->zipcode,
                    "City" => $client->address_city,
                    "District" => $client->getNeighborhood()
                ]
            ],
            "Payment" => [
                "Type" => "Boleto",
                "Amount" => self::amountRound($amount),
                "ExpirationDate" => date('Y-m-d', strtotime($boletoExpirationDate)),
                "Instructions" => $boletoInstructions
            ]
        ];
        if (App::environment() != 'production') {
            $fields['Payment']['Provider'] = 'Simulado';
        }
        $body = json_encode($fields);

        $merchantId         = Settings::findObjectByKey('braspag_merchant_id');
        $merchandtKey       = Settings::findObjectByKey('braspag_merchant_key');
        $header = [
            'Content-Type:  application/json',
            'MerchantId: '.$merchantId->value, 
            'MerchantKey: '.$merchandtKey->value       
        ];

        $apiRequest = self::apiRequest($url, $body, $header, self::POST_REQUEST);
        return $apiRequest;
    }

    private static function abbreviationState($state)
    {
        $state = strtolower($state);
        switch ($state) {
            case 'minas gerais':
                return "mg";
                break;
    
            default:
                return "mg";
                break;
        }
    }
}