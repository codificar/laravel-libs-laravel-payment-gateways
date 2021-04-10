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
    
    const URL_PROD = "https://ecommerce.adiq.io/v1";
    
    // const SUBORD_URL_PROD = "";

    const URL_DEV = "https://ecommerce-hml.adiq.io/v1";

    // const SUBORD_URL_DEV = "";

    const MCC   =   '5045';

    const FEE = 0;
    const CREDIT_CARD = 'Credit';
    const MASTER    ='Master';
    const INITIAL_INSTALLMENT_NUMBER = 1;
    const FINAL_INSTALLMENT_NUMBER = 1;
    // const MASTER_PERCENT = 2.36;
    // const VISA = "Visa";
    // const VISA_PERCENT = 2.36;  

    const ROUND_VALUE = 100;

    const NOTIFICATION_URL = "https://site.com.br/api/subordinados";

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

    // private static function apiSubordUrl() {
    //     if (App::environment() == 'production')
    //         return self::SUBORD_URL_PROD;
        
    //     return self::SUBORD_URL_DEV;
    // }

    public static function charge($payment, $user, $amount, $capture, $description)
    {
        $time = Carbon::now()->toDateTimeString();

        $orderId = self::getOrderId($time);

        $expirationDate = self::getExpirationDate($payment);

        $brand = self::getBrand($payment);

        $url = sprintf('%s/payments/', self::apiUrl());

        $phone = self::formatPhone($user->phone);

        $docType = ((strlen($user->document)) > 11) ? "CNPJ" : "CPF";

        $totalAmount = self::amountRound($amount);

        $fields = array (
            'MerchantOrderId'   =>  $orderId,
            'Customer'          =>  (object)array(
                'Name'          =>  $user->first_name.' '.$user->last_name,
                "email"         =>  $user->email,
                "Identity"      =>  $user->document,
                "identitytype"  =>  $docType,
                "Mobile"        =>  $phone,
                "Phone"         =>  $phone,
            ),
            'Payment'           =>  (object)array(
                'Type'          =>  self::CREDIT_CARD,
                'Amount'        =>  $totalAmount,
                'Installments'  =>  1,
                'Capture'       =>  $capture,
                'SoftDescriptor'    =>  Settings::findByKey('website_title'),
                'CreditCard'            =>  (object)array(
                    'CardNumber'        =>  $payment->getCardNumber(),
                    'Holder'            =>  $payment->getCardHolder(),
                    'ExpirationDate'    =>  $expirationDate,
                    'SecurityCode'      =>  $payment->getCardCvc(),
                    'Brand'             =>  $brand,
                    'SaveCard'          =>  false
                ),
                'fraudanalysis'     =>  (object)array(
                    'provider'      =>  'cybersource',
                    'Shipping'      =>  (object)array(
                        'Addressee' =>  $user->first_name.' '.$user->last_name
                    ),
                    'browser'       =>  (object)array(
                        // 'ipaddress'             =>  '179.221.103.151',
                        'browserfingerprint'    =>  $orderId
                    ),
                    'totalorderamount'          =>  $totalAmount,
                    'MerchantDefinedFields'     =>  array(
                        (object)array(
                            'id'    =>  1,
                            'value' =>  'Guest'
                        )
                    ),
                    

                )
            ),            
        );


        $body = json_encode($fields);

        $header = self::getHeader();

        $requestType = self::POST_REQUEST;

        $apiRequest = self::apiRequest($url, $body, $header, $requestType);

        return $apiRequest;
    }

    public static function chargeWithOrNotSplit(Payment $payment, Provider $provider = null, $totalAmount, $providerAmount = null, $description, $capture, $split)
    {
        $time = Carbon::now()->toDateTimeString();

        $orderId = self::getOrderId($time);

        $expirationDate = self::getExpirationDate($payment);

        $user = User::find($payment->user_id);

        $brand = self::getBrand($payment);

        $url = sprintf('%s/payments/', self::apiUrl());

        // if ($provider) {
        //     $ledgerBankAccount = LedgerBankAccount::findBy('provider_id', $provider->id);

        //     $subordinateId = self::checkProviderAccount($ledgerBankAccount);
        // }

        // $type = $split ? 'splittedcreditcard' : 'CreditCard'; 
        $type = self::CREDIT_CARD; 

        $header = self::getHeader();

        $docType = ((strlen($user->document)) > 11) ? "CNPJ" : "CPF";

        $phone = self::formatPhone($user->phone);

        $totalAmount = self::amountRound($totalAmount);

        $fields = array (
            'MerchantOrderId'   =>  $orderId,
            'Customer'          =>  (object)array(
                'Name'          =>  $user->first_name.' '.$user->last_name. ' ACCEPT',
                "email"         =>  $user->email,
                "Identity"      =>  $user->document,
                "identitytype"  =>  $docType,
                "Mobile"        =>  $phone,
                "Phone"         =>  $phone,
            ),
            'Payment'           =>  (object)array(
                'Type'          =>  $type,
                'Amount'        =>  $totalAmount,
                'Installments'  =>  1,
                'Capture'       =>  $capture,
                'SoftDescriptor'    =>  Settings::findByKey('website_title'),
                'CreditCard'            =>  (object)array(
                    'CardNumber'        =>  $payment->getCardNumber(),
                    'Holder'            =>  $payment->getCardHolder(),
                    'ExpirationDate'    =>  $expirationDate,
                    'SecurityCode'      =>  $payment->getCardCvc(),
                    'Brand'             =>  $brand,
                    'SaveCard'          =>  false
                ),
                'fraudanalysis'     =>  (object)array(
                    'provider'      =>  'cybersource',
                    'Shipping'      =>  (object)array(
                        'Addressee' =>  $user->first_name.' '.$user->last_name. ' ACCEPT'
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
                    ),
                    

                )
            ),            
        );

        if (App::environment() != 'production') {
            $fields['Payment']->Provider = 'Simulado';
        }

        // if ($capture && $provider) {
        //     $split = self::getSplitInfo($provider, $totalAmount);
        //     $fields['SplitPayments'] = $split['SplitPayments'];
        // }

        $body = json_encode($fields);

        $requestType = self::POST_REQUEST;

        $apiRequest = self::apiRequest($url, $body, $header, $requestType);

        return $apiRequest;
    }

    // public static function captureWithSplit(Transaction $transaction, $provider, $totalAmount, $providerAmount)
    // {

    //     $transactionToken = $transaction->gateway_transaction_id;

    //     $url = sprintf('%s/payments/%s/capture', self::apiUrl(), $transactionToken);

    //     $fields = self::getSplitInfo($provider, $transaction->gross_value);

    //     $body = json_encode($fields);

    //     // $body = null;

    //     $header = self::getHeader();

    //     $requestType = self::PUT_REQUEST;

    //     $apiRequest = self::apiRequest($url, $body, $header, $requestType);

    //     return $apiRequest;
        
    // }

    public static function capture(Transaction $transaction, $amount)
    {
        $transactionToken = $transaction->gateway_transaction_id;

        // $amount = 5;

        $url = sprintf('%s/payments/%s/capture', self::apiUrl(), $transactionToken);

        // if ($transaction->gross_value > $amount) {
        //     $url = $url."?amount=".$amount;
        // }

        $body = null;

        $header = self::getHeader();

        $requestType = self::PUT_REQUEST;

        $apiRequest = self::apiRequest($url, $body, $header, $requestType);

        return $apiRequest;
    }

    public static function refund(Transaction $transaction)
    {
        $transactionToken = $transaction->gateway_transaction_id;

        $url = sprintf('%s/payments/%s/cancel', self::apiUrl(), $transactionToken);

        $body = null;

        $header = self::getHeader();

        $requestType = self::PUT_REQUEST;

        $apiRequest = self::apiRequest($url, $body, $header, $requestType);

        return $apiRequest;
    }

    public static function retrieve($transaction)
    {
        $transactionToken = $transaction->gateway_transaction_id;

        $url = sprintf('%s/payments/%s', self::apiUrl(), $transactionToken);

        $body = null;

        $clientId       =   Settings::findObjectByKey('adiq_client_id');
        $clientSercret  =   Settings::findObjectByKey('adiq_client_secret');

        $header = self::getHeader();

        $requestType = self::GET_REQUEST;

        $apiRequest = self::apiRequest($url, $body, $header, $requestType);

        return $apiRequest;
    }

    // public static function createOrUpdateAccount($ledgerBankAccount)
    // {
    //     $url = sprintf('%s/api/subordinates/', self::apiSubordUrl());
    //     $document = $ledgerBankAccount->document;
        
    //     $provider = Provider::find($ledgerBankAccount->provider_id);

    //     $bankCode = Bank::getBankCode($ledgerBankAccount->bank_id);

    //     $docType = ((strlen($document)) > 11) ? "CNPJ" : "CPF";

    //     $phone = self::formatPhone($provider->phone);

    //     $state = self::abbreviationState($provider->state);

    //     if ($ledgerBankAccount->account_type == "conta_corrente") {
    //         $accountType = "CheckingAccount";
    //     } else {
    //         $accountType = "SavingsAccount";
    //     }

    //     $fields = array(
    //         'CorporateName' =>  $ledgerBankAccount->holder,
    //         'FancyName'     =>  $ledgerBankAccount->holder,
    //         'DocumentNumber'    =>  $ledgerBankAccount->document,
    //         'DocumentType'      =>  $docType,
    //         'MerchantCategoryCode'  =>  self::MCC,
    //         'ContactName'           =>  $ledgerBankAccount->holder,
    //         'ContactPhone'          =>  $phone,
    //         'MailAddress'           =>  $provider->email,
    //         'Website'               =>  '',
    //         'BankAccount'           =>  (object)array(
    //             'Bank'              =>  $bankCode,
    //             'BankAccountType'   =>  $accountType,
    //             'Number'            =>  $ledgerBankAccount->account,
    //             'VerifierDigit'     =>  $ledgerBankAccount->account_digit,
    //             'AgencyNumber'      =>  $ledgerBankAccount->agency,
    //             'AgencyDigit'       =>  $ledgerBankAccount->agency_digit,
    //             'DocumentNumber'    =>  $ledgerBankAccount->document,
    //             'DocumentType'      =>  $docType
    //         ),
    //         'Address'               =>  (object)array(
    //             'Street'            =>  $provider->address,
    //             'Number'            =>  $provider->address_number,
    //             'Complement'        =>  $provider->adress_complements,
    //             'Neighborhood'      =>  $provider->address_neighbour,
    //             'City'              =>  $provider->address_city,
    //             'State'             =>  $state,
    //             'ZipCode'           =>  $provider->zipcode
    //         ),
    //         'Agreement'             =>  (object)array(
    //             'Fee'               =>  self::FEE,
    //             'MerchantDiscountRates' =>  array(
    //                 (object)array(
    //                     'PaymentArrangement'    =>  (object)array(
    //                         'Product'           =>  self::CREDIT_CARD,
    //                         'Brand'             =>  self::MASTER
    //                     ),
    //                     'InitialInstallmentNumber'  =>  self::INITIAL_INSTALLMENT_NUMBER,
    //                     'FinalInstallmentNumber'    =>  self::FINAL_INSTALLMENT_NUMBER,
    //                     'Percent'                   =>  self::MASTER_PERCENT
    //                 ),
    //                 (object)array(
    //                     'PaymentArrangement'    =>  (object)array(
    //                         'Product'           =>  self::CREDIT_CARD,
    //                         'Brand'             =>  self::VISA
    //                     ),
    //                     'InitialInstallmentNumber'  =>  self::INITIAL_INSTALLMENT_NUMBER,
    //                     'FinalInstallmentNumber'    =>  self::FINAL_INSTALLMENT_NUMBER,
    //                     'Percent'                   =>  self::VISA_PERCENT
    //                 ),
                    
    //             ) 
    //         ),
    //         'Notification'          =>  (object)array(
    //             'Url'               =>  self::NOTIFICATION_URL,
    //             'Headers'           =>  array(
    //                 (object)array(
    //                     'Key'           =>  'Key1',
    //                     'Value'         =>  'Value'
    //                 )
                    
    //             )
    //         )
    //     );

    //     $body = json_encode($fields);

    //     $header = self::getHeader();

    //     $requestType = self::POST_REQUEST;

    //     $apiRequest = self::apiRequest($url, $body, $header, $requestType);

    //     return $apiRequest;
    // }

    private static function getOrderId($time)
    {
        $value = str_replace(' ', '', $time);
        $value = str_replace('-', '', $value);
        $value = str_replace(':', '', $value);

        return $value;
    }

    private static function formatPhone($phone)
    {
        $phone = str_replace('(', '', $phone);
        $phone = str_replace(')', '', $phone);
        $phone = str_replace('-', '', $phone);
        $phone = str_replace('+', '', $phone);
        $phone = str_replace(' ', '', $phone);
        $phone = substr($phone, -11);

        return $phone;
    }

    private static function getExpirationDate($payment)
    {
        $month = $payment->getCardExpirationMonth();
        $year = $payment->getCardExpirationYear();
        $x = strlen($month);
        $retMonth = (strlen($month) < 2) ? '0'.$month : $month ;
        $retYear = (strlen($year) < 4) ? '20'.$year : $year ;
        $expDate = trim(sprintf("%s/%s", $retMonth, $retYear));
        //$expDate = str_replace('\n', '', $expDate);
        
        return $expDate;
    }

    private static function getBrand($payment)
    {
        $brand = Payment::getBrand($payment);
        $brand = strtolower($brand);
        $brand = ucfirst($brand);

        if ($brand == 'Mastercard') {
            $brand = self::MASTER;
        }

        return $brand;
    }

    public static function getAdiqFee()
    {
        return self::FEE;
    }

    // public static function checkProviderAccount($ledgerBankAccount)
    // {
    //     $subordinateId = $ledgerBankAccount->recipient_id;

    //     if ($subordinateId == '' || $subordinateId == 'empty') {
    //         $response = self::createOrUpdateAccount($ledgerBankAccount);
            
    //     } else {
    //         $response = self::getAdiqAccount($subordinateId);
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

    // public static function getAdiqAccount($subordinateId)
    // {
    //     $url = sprintf('%s/api/subordinates/%s', self::apiSubordUrl(), $subordinateId);

    //     $header = self::getHeader();

    //     $requestType = self::GET_REQUEST;

    //     $body = null;

    //     $apiRequest = self::apiRequest($url, $body, $header, $requestType);

    //     return $apiRequest;
    // }

    private static function amountRound($amount)
    {
        $amount = $amount * self::ROUND_VALUE;
        $type = gettype($amount);
        $amount = (int)$amount;

        return $amount;
    }

    // private static function getSplitInfo($provider, $totalAmount)
    // {
    //     $ledgerBankAccount = LedgerBankAccount::findBy('provider_id', $provider->id);

    //     $totalAmount = self::amountRound($totalAmount);

    //     $providerPercentage = Settings::findObjectByKey('provider_amount_for_each_request_in_percentage');
    //     $percentage = 100 - (int)$providerPercentage->value;

    //     $fields = array(
    //         'SplitPayments'     =>  array(
    //             (object)array(
    //                 'SubordinateMerchantId'     =>  $ledgerBankAccount->recipient_id,
    //                 'Amount'                    =>  $totalAmount,
    //                 'Fares'                     =>  (object)array(
    //                     'Mdr'           =>  $percentage,
    //                     'Fee'           =>  0
    //                 )
    //             )
    //         )
    //     );

    //     return $fields;
    // }

    private static function getHeader()
    {
        $token      =   self::makeToken();
        $adiqToken  =   Settings::findObjectByKey('adiq_token');
        $header = array (
            'Content-Type: application/json; charset=UTF-8',
            'Accept: application/json',
            'Authorization: Bearer '.isset($adiqToken) && isset($adiqToken->token)  ? $adiqToken->value : ""
        );

        return $header;
    }

    private static function getrequestMessage($result)
    {
        $type = gettype($result);
        
        if ($type == 'array') {
            $response = array();
            foreach ($result as $value) {
                array_push($response, array(
                    'code'      =>  $value->Code,
                    'message'   =>  $value->Message
                ));
            }
            $response = $response;
        } else {
            $x = $result->access_token;
            if (isset($result->Payment->PaymentId)) {
                $response = array(
                    'success'           =>  true,
                    'transaction_id'    =>  $result->Payment->PaymentId
                );
            } elseif (isset($result->access_token)) {
                $response = array(
                    'success'           =>  true,
                    'transaction_id'    =>  $result->access_token
                );
            }
            
        }

        return $response;
    }

    private static function makeToken()
    {
        $clientId       =   Settings::findObjectByKey('adiq_client_id');
        $clientSercret  =   Settings::findObjectByKey('adiq_client_secret');

        $concateString = base64_encode($clientId->value.':'.$clientSercret->value);

        $url = sprintf('%s/auth/oauth2/v1/token', self::apiUrl());

        $body = "grant_type=client_credentials";
        
        $header = array (
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Basic '.$concateString, 
        );

        $requestType = self::POST_REQUEST;

        $apiRequest = self::apiRequest($url, $body, $header, $requestType);


        try {
            $token = Settings::findObjectByKey('adiq_token');
            $token->value = $apiRequest->data->access_token;
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
                // curl_setopt($session, CURLOPT_POSTFIELDS, json_encode(array()));
            }
            \
            
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

    // /**
	//  * Função para gerar boletos de pagamentos
	//  * @param int $amount valor do boleto
	//  * @param User/Provider $client instância do usuário ou prestador
	//  * @param string $postbackUrl url para receber notificações do status do pagamento
	//  * @param string $boletoExpirationDate data de expiração do boleto
	//  * @param string $boletoInstructions descrição no boleto
	//  * @return array
	//  */
    // public static function billetCharge ($amount, $client, $postbackUrl, $boletoExpirationDate, $boletoInstructions)
    // {
    //     $url = sprintf('%s/payments/', self::apiUrl());
    //     $orderId = self::getOrderId(Carbon::now()->toDateTimeString());

    //     $fields = [
    //         "MerchantOrderId" => $orderId,
    //         "Customer" => [
    //             "Name" => $client->getFullName(),
    //             "Identity" => $client->getDocument(),
    //             "IdentityType" => "CPF",
    //             "Address" =>  [  
    //                 "Street" => $client->getStreet(),
    //                 "Number" => $client->getStreetNumber(),
    //                 "Complement" => $client->address_complements,
    //                 "ZipCode" => $client->zipcode,
    //                 "City" => $client->address_city,
    //                 "District" => $client->getNeighborhood()
    //             ]
    //         ],
    //         "Payment" => [
    //             "Type" => "Boleto",
    //             "Amount" => self::amountRound($amount),
    //             "ExpirationDate" => date('Y-m-d', strtotime($boletoExpirationDate)),
    //             "Instructions" => $boletoInstructions
    //         ]
    //     ];
    //     if (App::environment() != 'production') {
    //         $fields['Payment']['Provider'] = 'Simulado';
    //     }
    //     $body = json_encode($fields);

    //     $clientId         = Settings::findObjectByKey('adiq_client_id');
    //     $merchandtKey       = Settings::findObjectByKey('adiq_client_secret');
    //     $header = self::getHeader();

    //     $requestType = self::POST_REQUEST;

    //     $apiRequest = self::apiRequest($url, $body, $header, $requestType);
    //     return $apiRequest;
    // }

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