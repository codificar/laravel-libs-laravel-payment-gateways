<?php

namespace Codificar\PaymentGateways\Libs;
use Carbon\Carbon;

//models do sistema
use Illuminate\Support\Facades\Log;
use Exception;
use App;
use Payment;
use Provider;
use Transaction;
use User;
use LedgerBankAccount;
use Settings;
use Bank;
use Codificar\PaymentGateways\Libs\handle\phone\PhoneNumber;
use DateTime;

class IpagApi
{
    const URL_PROD  =   "https://api.ipag.com.br/service";
    const URL_DEV   =   "https://sandbox.ipag.com.br/service";

    const ROUND_VALUE   =   100;
    const APP_TIMEOUT   =   200;

    const POST_REQUEST  =   'POST';
    const GET_REQUEST   =   'GET';
    const PUT_REQUEST   =   'PUT';

    const ANTIFRAUD_APPROVED = 'approved';
    const ANTIFRAUD_PENDING = 'pending';
    const STATUS_CODE_PRE_AUTHORIZED = 5;

    /**
     * Defines gateway base URL
     *
     * @return String
     */
    private static function apiUrl()
    {
        if (App::environment() == 'production') {
            return self::URL_PROD;
        }
        return self::URL_DEV;
    }

    /**
     * Authorizes a card payment, reserving service amount
     *
     * @param Object       $payment     Object that represents a card on system.
     * @param Object       $user        Object that represents a user on system.
     * @param Float        $amount      Total value of service.
     * @param Boolean      $capture     Informs if charge have immediate capture.
     *
     * @return Object      {
     *                      'success',
     *                      'data',
     *                      ?'message' //if it fails, with false success
     *                     }
     */
    public static function charge(Payment $payment, User $user, $amount, $capture)
    {
        $url = sprintf('%s/payment', self::apiUrl());

        $header     =   self::getHeader(true);
        $body       =   self::getBody($payment, $amount, null, $capture, null, $user);
        $chargeRequest =   self::apiRequest($url, $body, $header, self::POST_REQUEST);

        return $chargeRequest;
    }

    /**
     * Authorizes a card payment, reserving service amount, being able uses split rules
     *
     * @param Object       $payment         Object that represents a card on system.
     * @param Object       $provider        Object that represents a provider on system.
     * @param Float        $amount          Total value of service.
     * @param Float        $providerAmount  Corresponding value to provider service.
     * @param Boolean      $capture         Informs if charge have immediate capture.
     *
     * @return Object      {
     *                      'success',
     *                      'data',
     *                      ?'message' //if it fails, with false success
     *                     }
     */
    public static function chargeWithOrNotSplit(Payment $payment, Provider $provider = null, $amount, $providerAmount = null, $capture)
    {
        $url = sprintf('%s/payment', self::apiUrl());

        $header     =   self::getHeader(true);
        $body       =   self::getBody($payment, $amount, $providerAmount, $capture, $provider);
        $chargeSplitRequest =   self::apiRequest($url, $body, $header, self::POST_REQUEST);

        if(self::isForceCapture($chargeSplitRequest, $capture)){
            $chargeSplitRequest = self::captureById($chargeSplitRequest->data->id, $amount);
        }
        
        return $chargeSplitRequest;
    }

    /**
     * Verify force capture in request
     * @param object $chargeSplitRequest
     * @param bool $capture
     * 
     * @return bool
     */
    public static function isForceCapture(object $chargeSplitRequest, $capture): bool
    {
        return $chargeSplitRequest 
            && $chargeSplitRequest->success 
            && isset($chargeSplitRequest->data->attributes->status->code)
            && $chargeSplitRequest->data->attributes->status->code == self::STATUS_CODE_PRE_AUTHORIZED
            && isset($chargeSplitRequest->data->attributes->antifraud->status) 
            && ($chargeSplitRequest->data->attributes->antifraud->status == self::ANTIFRAUD_PENDING ||
                $chargeSplitRequest->data->attributes->antifraud->status == self::ANTIFRAUD_APPROVED) 
            && $capture;
    } 

    /**
     * Captures a authorized charge by ID
     *
     * @param Object       $transaction     Object that represents a transaction on system.
     *
     * @return Object      {
     *                      'success',
     *                      'data',
     *                      ?'message' //if it fails, with false success
     *                     }
     */
    public static function capture(Transaction $transaction, $amount)
    {
        $url = sprintf('%s/capture?id=%s', self::apiUrl(), $transaction->gateway_transaction_id);

        if($amount)
            $url = sprintf('%s&valor=%s', $url, $amount);

        $body       =   null;
        $header     =   self::getHeader(true);
        $captureRequest =   self::apiRequest($url, $body, $header, self::POST_REQUEST);

        return $captureRequest;
    }

    /**
     * Capture gateway charge by transaction_id 
     * @param int $transactionId
     * @param float $amount
     * 
     * @return mixed $captureRequest
     * 
     */
    public static function captureById($transactionId, $amount)
    {
        $url = sprintf('%s/capture?id=%s', self::apiUrl(), $transactionId);

        if($amount)
            $url = sprintf('%s&valor=%s', $url, $amount);

        $body       =   null;
        $header     =   self::getHeader(true);
        $captureRequest =   self::apiRequest($url, $body, $header, self::POST_REQUEST);

        return $captureRequest;
    }

    /**
     * Captures a authorized charge by ID, using split rules
     *
     * @param Object       $transaction     Object that represents a transaction on system.
     * @param Object       $provider        Object that represents a provider on system.
     *
     * @return Object      {
     *                      'success',
     *                      'data',
     *                      ?'message' //if it fails, with false success
     *                     }
     */
    public static function captureWithSplit(Transaction $transaction, Provider $provider, $providerAmount, $newAmount = null)
    {
        $splitBody      =   null;
        $splitHeader    =   self::getHeader();
        $splitUrl       =   sprintf('%s/resources/split_rules?transaction=%s', self::apiUrl(), $transaction->gateway_transaction_id);
        $splitRequest   =   self::apiRequest($splitUrl, $splitBody, $splitHeader, self::GET_REQUEST);

        if(
            !isset($splitRequest->success) ||
            !$splitRequest->success ||
            !isset($splitRequest->data->data) ||
            !count($splitRequest->data->data)
        ){
            $ruleResponse = self::setSplitRule($transaction, $provider->id, 'receiver_id', $providerAmount);

            if(
                !$ruleResponse ||
                !isset($ruleResponse->success) ||
                !$ruleResponse->success
            ){
                \Log::error("Set split rule new fail: " . print_r($ruleResponse, 1));
                return (object)array(
                    "success"   => false,
                    "message"   => 'Set split rule new fail'
                );
            }
        }
        else
        {
            if($newAmount)
            {
                $deleteRuleResponse = self::deleteSplitRule($transaction->gateway_transaction_id, $splitRequest->data->data[0]->id);

                if(
                    !isset($deleteRuleResponse->success) ||
                    !$deleteRuleResponse->success
                ){
                    \Log::error("Delete split rule fail: " . print_r($deleteRuleResponse, 1));
                    return (object)array(
                        "success"   => false,
                        "message"   => 'Delete split rule fail'
                    );
                }

                $ruleResponse = self::setSplitRule($transaction, $provider->id, 'receiver_id', $providerAmount);

                if(
                    !$ruleResponse ||
                    !isset($ruleResponse->success) ||
                    !$ruleResponse->success
                ){
                    \Log::error("Set split rule change fail: " . print_r($ruleResponse, 1));
                    return (object)array(
                        "success"   => false,
                        "message"   => 'Set split rule change fail'
                    );
                }
            }
        }

        $url = sprintf('%s/capture?id=%s', self::apiUrl(), $transaction->gateway_transaction_id);

        if($newAmount)
            $url = sprintf('%s&valor=%s', $url, $newAmount);

        $body       =   null;
        $header     =   self::getHeader(true);
        $captureSplitRequest =  self::apiRequest($url, $body, $header, self::POST_REQUEST);

        return $captureSplitRequest;
    }

    /**
     * Retrives a gateway transaction by ID
     *
     * @param Object       $transaction     Object that represents a transaction on system.
     *
     * @return Object      {
     *                      'success',
     *                      'data',
     *                      ?'message' //if it fails, with false success
     *                     }
     */
    public static function retrieve(Transaction $transaction)
    {
        $url = sprintf('%s/consult?id=%s', self::apiUrl(), $transaction->gateway_transaction_id);

        $body       =   null;
        $header     =   self::getHeader(true);
        $retrieveRequest =  self::apiRequest($url, $body, $header, self::GET_REQUEST);

        return $retrieveRequest;
    }

    public static function refund(Transaction $transaction)
    {
        $url = sprintf('%s/cancel?id=%s', self::apiUrl(), $transaction->gateway_transaction_id);

        $body       =   null;
        $header     =   self::getHeader(true);
        $refundRequest = self::apiRequest($url, $body, $header, self::POST_REQUEST);

        return $refundRequest;
    }

    public static function createOrUpdateAccount(LedgerBankAccount $ledgerBankAccount)
    {
        // to create a new account
        $url = sprintf('%s/resources/sellers', self::apiUrl());
        $verb = self::POST_REQUEST;

        $phone          =   null;
        $provider       =   Provider::find($ledgerBankAccount->provider_id);
        $bank           =   Bank::find($ledgerBankAccount->bank_id);

        $phone = '(31) 99999-9999';
        if($provider && $provider->phone) {
            $phone = preg_replace("/[^0-9]/", "", $provider->phone);
            try {
                $phoneLib = new PhoneNumber($phone);
                $phone = $phoneLib->getPhoneNumberFormatedBR(false);
            } catch (\Exception $e) {
                \Log::error($e->getMessage() . $e->getTraceAsString());
            }
        }
        $bankObject = (object) array();
        if($bank && $ledgerBankAccount &&
            $bank->code && $ledgerBankAccount->agency &&
            $ledgerBankAccount->account && 
            $ledgerBankAccount->account_digit
        ) {
            $bankObject = (object)array(
                'code'          =>  $bank->code,
                'agency'        =>  $ledgerBankAccount->agency,
                'account'       =>  $ledgerBankAccount->account.$ledgerBankAccount->account_digit
            );
        }

        $login = substr(uniqid(), 0, 50);
        if($provider->email) {
            $login = substr($provider->email, 0, 50);
        }

        $password = substr(uniqid(), 0, 6);
        if($provider->document) {
            $passwordRemask = self::remaskDocument($provider->document);
            if($passwordRemask) {
                $password = substr($passwordRemask, 0, 6);
            }
        }


        $fields = (object)array(
            'login'         => $login,
            'password'      => $password,
            'name'          => $ledgerBankAccount->holder,
            'email'         => $provider->email,
            'phone'         => $phone,
            'bank'          => $bankObject  
        );

        $documentRemask = self::remaskDocument($ledgerBankAccount->document);
        if($documentRemask) {
            $fields = array_merge((array)$fields, ['cpf_cnpj'=> $documentRemask]); //document remask BR
        } else {
            \Log::info('[remaskDocument] Document Invalid $ledgerBankAccount: ' . json_encode($ledgerBankAccount) );
        }
        if($ledgerBankAccount->document && strlen($ledgerBankAccount->document) >= 11) {
            
            $birthday = $ledgerBankAccount->birthday_date;
            $date = DateTime::createFromFormat('Y-m-d', $birthday);
            
            if ($date) {
                $birthday = $date->format('Y-m-d');
            } else {
                $birthday = '1970-01-01';
            }
            $documentOwnerRemask = self::remaskDocument($provider->document);
            if(!$documentRemask) {
                \Log::info('[remaskDocument] Document Invalid $provider: ' . json_encode($provider) );
            }

            $fields['owner'] = (object)array(
                'name'      =>  $provider->first_name . $provider->last_name,
                'email'     =>  $provider->email,
                'cpf'       =>  $documentOwnerRemask, //document remask BR
                'phone'     =>  $phone,
                'birthdate' =>  $birthday
            );
        }

        $header     =   self::getHeader();
        $body       =   json_encode($fields);

        // tenta criar o seller
        $accountRequest = self::apiRequest($url, $body, $header, $verb);
        throw new Exception(print_r($accountRequest,1));
        // caso dê erro pq já existe o seller ele tenta atualizar por document
        if($documentRemask && !$accountRequest->success && 
        strpos($accountRequest->message, 'already exists') !== false) {
            if(!$accountRequest->success && strpos($accountRequest->message, 'Seller with Login') !== false) {
                $fields['login'] = substr(uniqid() . $login, 0, 50);
                $body = json_encode($fields);
            } else {
                $url = sprintf('%s/resources/sellers?cpf_cnpj=%s', self::apiUrl(), $documentRemask);
                $verb = self::PUT_REQUEST;
                unset($fields['login']);
                $body = json_encode($fields);    
            }
            // tenta criar/atualizar o seller
            $accountRequest = self::apiRequest($url, $body, $header, $verb);
            throw new Exception(print_r($accountRequest,1));
            // verifica se é o login que está duplicado e altera
            if(!$accountRequest->success && strpos($accountRequest->message, 'Seller with Login') !== false) {
                $fields['login'] = substr(uniqid() . $login, 0, 50);
                $body = json_encode($fields);
                // tenta atualizar o seller
                $accountRequest = self::apiRequest($url, $body, $header, $verb);
                throw new Exception("2" . print_r($accountRequest,1));
            }
        }
        
        if($accountRequest && isset($accountRequest->data->attributes->is_active) && $accountRequest->data->attributes->is_active === false)
            $accountRequest = self::activeSeller($accountRequest->data->id);

        throw new Exception(print_r("active" . $accountRequest,1));
        return $accountRequest;
    }

    public static function getSeller($sellerId)
    {
        $url = sprintf('%s/resources/sellers?id=%s', self::apiUrl(), $sellerId);

        $body       =   null;
        $header     =   self::getHeader();
        $sellerRequest =   self::apiRequest($url, $body, $header, self::GET_REQUEST);
        return $sellerRequest;
    }

    public static function getSellerByDocument($sellerDocument)
    {
        $url = sprintf('%s/resources/sellers?cpf_cnpj=%s', self::apiUrl(), $sellerDocument);

        $body           =   null;
        $header         =   self::getHeader();
        $sellerRequest  =   self::apiRequest($url, $body, $header, self::GET_REQUEST);

        return $sellerRequest;
    }

    public static function getSellerByLedgerBankAccount(\LedgerBankAccount $ledgerBankAccount)
    {
        $sellerId = $ledgerBankAccount->recipient_id;

        if($sellerId == '' || $sellerId == 'empty' || $sellerId === null) {
            
            $isDocument = isset($ledgerBankAccount->document) && !empty($ledgerBankAccount->document);
            $isUser = isset($ledgerBankAccount->user_id) && !empty($ledgerBankAccount->user_id);
            $isProvider = isset($ledgerBankAccount->provider_id) && !empty($ledgerBankAccount->provider_id);
            
            if($isUser) {
                $userEmail = \User::where(['id' => $ledgerBankAccount->user_id])->first()->email;
                $url = sprintf('%s/resources/sellers?email=%s', self::apiUrl(), $userEmail);
            } else if($isProvider) {
                $providerEmail = \Provider::where(['id' => $ledgerBankAccount->provider_id])->first()->email;
                $url = sprintf('%s/resources/sellers?email=%s', self::apiUrl(), $providerEmail);
            }
        } else {
            $url = sprintf('%s/resources/sellers?id=%s', self::apiUrl(), $sellerId);
        }

        $body       =   null;
        $header     =   self::getHeader();
        
        $sellerRequest =   self::apiRequest($url, $body, $header, self::GET_REQUEST);
        
        return $sellerRequest;
    }

    public static function checkProviderAccount(LedgerBankAccount $ledgerBankAccount)
    {
        \Log::debug('LBA');
        \Log::debug(print_r($ledgerBankAccount,1));
        $sellerId = $ledgerBankAccount->recipient_id;
        $response  = null;
        if(isset($sellerId)) {
            $response = self::getSeller($sellerId);
        }

        if(!$response || !isset($response->data->id))
        {
            $response = self::createOrUpdateAccount($ledgerBankAccount);
        }
        \Log::debug('resp');
        \Log::debug(print_r($response,1));
        if($response->success)
        {
            $result = (object)array(
                'success'           =>  true,
                'recipient_id'      =>  $response->data->id,
                'is_active'         =>  $response->data->attributes->is_active
            );
            $ledgerBankAccount->recipient_id = $response->data->id;
            \Log::debug('save');
            $ledgerBankAccount->save();
        }
        else
        {
                \Log::debug('else');
            #TODO remover após job de recriar recipients ao trocar gateway
            $newAccount = self::createOrUpdateAccount($ledgerBankAccount);
            throw new Exception("new" . print_r($newAccount,1));
            if($newAccount->success)
            {
                $ledgerBankAccount->recipient_id = $newAccount->data->id;
                \Log::debug('save2');
                $ledgerBankAccount->save();
                $result = (object)array(
                    'success'       =>  true,
                    'recipient_id'  =>  $ledgerBankAccount->recipient_id,
                    'is_active'     =>  $newAccount->data->attributes->is_active
                );
            }
            else
            {
                \Log::debug('else2');
                $result = (object)array(
                    'success'       =>  false,
                    'recipient_id'  =>  ""
                );
            }
        }

        \Log::debug('active');
        if(isset($result->is_active) && $result->is_active === false)
            self::activeSeller($result['recipient_id']);

        \Log::debug('pos active');
        return $result;
    }
    /**
	 * Função para gerar boletos de pagamentos
	 * @param int $amount valor do boleto
	 * @param User/Provider $client instância do usuário ou prestador
	 * @param string $boletoExpirationDate data de expiração do boleto
	 */
    public static function billetCharge($amount, $client, $boletoExpirationDate)
    {
        $url = sprintf('%s/payment', self::apiUrl());

        $header     =   self::getHeader(true);
        $body       =   self::getBody(null, $amount, null, false, null, $client, $boletoExpirationDate);
        $billetRequest =   self::apiRequest($url, $body, $header, self::POST_REQUEST);

        return $billetRequest;
    }

    public static function pixCharge($amount, $user, $provider)
    {
        $url = sprintf('%s/payment', self::apiUrl());

        $header     =   self::getHeader(true, true);
        $body       =   self::getBody(null, $amount, null, true, $provider, $user, null, true);
        $pixRequest =   self::apiRequest($url, $body, $header, self::POST_REQUEST);

        return $pixRequest;
    }

    public static function setSplitRule(Transaction $transaction, $providerId, $sellerIndex, $providerAmount)
    {
        $ruleUrl       =   sprintf('%s/resources/split_rules?transaction=%s', self::apiUrl(), $transaction->gateway_transaction_id);

        $ruleHeader    =   self::getHeader();
        $ruleBody      =   json_encode(self::getSplitInfo($providerId, $providerAmount, $sellerIndex));
        $ruleRequest   =   self::apiRequest($ruleUrl, $ruleBody, $ruleHeader, self::POST_REQUEST);

        return $ruleRequest;
    }

    public static function deleteSplitRule($gatewayTransactionId, $ruleId)
    {
        $ruleUrl       =   sprintf('%s/resources/split_rules?transaction=%s&id=%s', self::apiUrl(), $gatewayTransactionId, $ruleId);

        $ruleBody      =   null;
        $ruleHeader    =   self::getHeader();
        $ruleRequest   =   self::apiRequest($ruleUrl, $ruleBody, $ruleHeader, self::POST_REQUEST);

        return $ruleRequest;
    }

    public static function activeSeller($sellerId)
    {
        $activeUrl       =   sprintf('%s/resources/sellers?id=%s', self::apiUrl(), $sellerId);

        $activeBody      =   json_encode(["is_active"=>true]);
        $activeHeader    =   self::getHeader();
        $activeRequest   =   self::apiRequest($activeUrl, $activeBody, $activeHeader, self::PUT_REQUEST);

        return $activeRequest;
    }

    private static function getOrderId()
    {
        list($microSeconds, $seconds) = explode(" ", microtime());
        $orderId = $seconds.substr($microSeconds, 2, -3);

        return $orderId;
    }

    private static function getExpirationDate($payment)
    {
        $month = $payment->getCardExpirationMonth();
        $year = $payment->getCardExpirationYear();

        $expDate[0] = str_pad($month, 2, '0', STR_PAD_LEFT);
        $year  = $year % 100;
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

    // public static function amountRound($amount)
    // {
    //     $amount = $amount * self::ROUND_VALUE;
    //     $amount = (int)$amount;

    //     return $amount;
    // }

    private static function getBody($payment = null, $amount, $providerAmount, $capture = false, $provider = null, $client = null, $billetExpiry = null, $isPix = false)
    {
        $paymentDocument = null;

        if($payment) {
            if($payment->user_id) {
                $client = User::find($payment->user_id);
            } else if($payment->provider_id) {
                $client = Provider::find($payment->provider_id);
            }

            if($payment->document){
                $paymentDocument = $payment->document;
            }else{
                $paymentDocument = $client->document;
            }
            
        }

        $cnpjMask = "%s%s.%s%s%s.%s%s%s/%s%s%s%s-%s%s";
        $cpfMask = "%s%s%s.%s%s%s.%s%s%s-%s%s";
        
        $document = null;
        if($client && isset($client->document) && !empty($client->document)) {
            try {
                $document = $client->document ? preg_replace( '/[^0-9]/', '', $client->document ) : '';
                if((strlen($document)) > 11) {
                    $mask = $cnpjMask;
                }
                if((strlen($document)) == 11) {
                    $mask = $cpfMask;
                }
                if($mask) {
                    $document = vsprintf($mask, str_split($document));
                }
            } catch(\Exception $e) {
                \Log::error($e->getMessage() . $e->getTraceAsString());
            }
        }

        if($payment)
        {
            $expirationDate = self::getExpirationDate($payment);

            $cardNumber = $payment->getCardNumber();

            //mask to card, 13 to 20 digits
            $cardLength = strlen($cardNumber);
            if($cardLength > 16)
                $regexCard = '/^(\d{4})(\d{4})(\d{4})(\d{4})([0-9]{0,4})$/';
            else
                $regexCard = '/^(\d{4})(\d{4})(\d{4})([0-9]{1,4})$/';
            preg_match($regexCard, $cardNumber,  $matches);
            $cardMask = implode(' ', array_slice($matches, 1));

            $type   = 'card';
            $method = self::getBrand($payment);
        }
        else if($isPix)
        {
            $type   = $method = 'pix';
        }
        else
        {
            $type   = 'boleto';
            $method = Settings::getBilletProvider();
        }

        if($client && strlen($client->state) > 2) {
            $client->state = self::getMinStateString($client->state);
        }

        $orderId        =   self::getOrderId();
        $requestId      =   $client && $client->getLastRequest() 
            ? $client->getLastRequest()->id 
            : $orderId;

        $phone = '(31) 99999-9999';
        if($client && $client->phone) {
            $phone = preg_replace('/\D/', '', $client->phone);
            try {
                $phoneLib = new PhoneNumber($client->phone);
                $phone = $phoneLib->getPhoneNumberFormatedBR(false);
            } catch (\Exception $e) {
                \Log::error($e->getMessage() . $e->getTraceAsString());
            }
        }

        $fields         =   (object)array(
            'amount'            =>  floatval($amount),
            'order_id'          =>  $orderId,
            'customer'          =>  (object)array(
                'name'          =>  $client->first_name.' '.$client->last_name,
                'email'         =>  $client->email,
                'phone'         =>  $phone,
                'cpf_cnpj'      =>  $document,
                'billing_address'=>  (object)array(
                    'street'    =>  $client->address,
                    'number'    =>  $client->address_number,
                    'district'  =>  $client->address_neighbour,
                    'complement'=>  $client->address_complements,
                    'city'      =>  $client->address_city,
                    'state'     =>  $client->state,
                    'zipcode'   =>  preg_replace('/\D/', '', $client->zipcode)
                )
            ),
            'payment'           =>  (object)array(
                'type'          =>  $type,
                'capture'       =>  $capture,
                'method'        =>  $method,
                'installments'  =>  1,
                'fraud_analysis'=>  (boolean) Settings::findByKey('ipag_antifraud')
            ),
            'products'          =>  array(
                (object)array(
                    'name'          =>  substr((string) Settings::findByKey('gateway_product_title'), 0, 80)." - ".$requestId,
                    'description'   =>  substr((string) Settings::findByKey('gateway_product_title'), 0, 254),
                    'unit_price'    =>  floatval(number_format($amount, 2)),
                    'quantity'      =>  1,
                    'sku'           =>  "$requestId"
                )
            )
        );

        if($payment)
        {
            $cardFields = (object)array(
                'card'  =>  (object)array(
                    'holder'        =>  $payment->getCardHolder(),
                    'number'        =>  $cardMask,
                    'expiry_month'  =>  $expirationDate[0],
                    'expiry_year'   =>  $expirationDate[1],
                    'cvv'           =>  $payment->getCardCvc()
                )
            );

            $fields->payment->card = $cardFields->card;
        }
        else if($type == 'boleto')
        {
            $billetFields = (object)array(
                'boleto'  =>  (object)array(
                    'due_date'      =>  Carbon::parse($billetExpiry)->format('Y-m-d'),
                    'instructions'  =>  (object)array( Settings::getBilletInstructions() )
                )
            );

            $productFields = (object)array(
                'products'  =>  (object)array(
                    (object)array(
                        'name'          =>  Settings::findByKey('website_title'),
                        'unit_price'    =>  floatval(number_format($amount, 2)),
                        'quantity'      =>  1
                    )
                )
            );

            $fields->payment->boleto    =   $billetFields->boleto;
            $fields->products           =   $productFields->products;
        }

        if($provider && isset($provider->id) && ($type == 'card' || $type == 'pix'))
        {
            $split = self::getSplitInfo($provider->id, $providerAmount, 'seller_id');
            $fields->split_rules = [$split];
        }
        return json_encode($fields);
    }

    /**
    * Get split information
    *
    * EX: 
    * Transação no valor de R$100,00
    * Taxa: 4,99%.
    * Necessário realizar split de R$50,00 para o vendedor #1.
    *
    * 1. Você deseja repassar toda a taxa ao vendedor#1? 
    * - Se sim, neste caso é necessário calcular o valor da taxa do seu lado, e realizar um split já com o desconto. Ex.: R$50,00 - R$4,99 = R$45,01.
    * - OBS: É importante que a flag "charge_processing_fee" esteja com valor "false", ou seja omitido o campo. 
    * 
    * 2. Você deseja repassar R$50,00 e a taxa proporcional ao valor deverá ser descontada? 
    * - Se sim, neste caso basta enviar o valor de R$50,00, e ativar a flag "charge_processing_fee" enviado como "true". 
    * - Com isso o valor final será de R$50,00 - (R$50,00 * 4,99%) = R$47,50.
    *
    * 3. Você deseja repassar exatamente R$50,00 sem nenhum desconto. 
    * - Neste caso basta enviar o valor de R$50,00 e não ativar a flag "charge_processing_fee".
    *
    *
    * Está sendo utilizado nesse caso a opção 3.
    *
    * @param int $providerId
    * @param float $providerAmount
    * @param int $sellerId
    *
    * @return array array with information about split to specific seller id
    *
    */
    private static function getSplitInfo($providerId, $providerAmount, $sellerIndex)
    {
        $ledgerBankAccount = LedgerBankAccount::where('provider_id', $providerId)->first();
        if(!$ledgerBankAccount)
             throw new Exception('Failed to recover BankAccount');

        $sellerId = self::checkProviderAccount($ledgerBankAccount);
        if(!isset($sellerId->success) || (isset($sellerId->success) && !$sellerId->success))
            throw new Exception(print_r($sellerId,1));

        $fields = (object)array(
            $sellerIndex            =>  $sellerId->recipient_id,
            'amount'                =>  floatval(number_format($providerAmount, 2)),
            'liable'                =>  false,
            'charge_processing_fee' =>  false
        );

        return $fields;
    }

    private static function getHeader($useVersion = false, $isPix = false)
    {
        $version    =   ['x-api-version: 2'];
        $token      =   self::makeToken($isPix);
        $ipagToken  =   Settings::findObjectByKey('ipag_token');
        $basic      =   $ipagToken && isset($ipagToken->value)  ? $ipagToken->value : $token;

        $header = array (
            'Content-Type: application/json',
            'Authorization: Basic '.$basic
        );

        $versionSettings  =   Settings::findObjectByKey('pix_ipag_version', '1');
        $isVersion2 = isset($versionSettings) && !empty($versionSettings) && 
            isset($versionSettings->value) && !empty($versionSettings->value) &&
            $versionSettings->value == '2';

        if($useVersion || $isVersion2) {
            $header = array_merge($header, $version);
        }

        return $header;
    }

    private static function makeToken($isPix = false)
    {
        
        if($isPix) {
            $ipagId     =   Settings::findObjectByKey('pix_ipag_api_id');
            $ipagKey    =   Settings::findObjectByKey('pix_ipag_api_key');
        } else {
            $ipagId     =   Settings::findObjectByKey('ipag_api_id');
            $ipagKey    =   Settings::findObjectByKey('ipag_api_key');
        }

        
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

            if($fields)
                curl_setopt($session, CURLOPT_POSTFIELDS, ($fields));
            else
                array_push($header, 'Content-Length: 0');

            curl_setopt($session, CURLOPT_TIMEOUT, self::APP_TIMEOUT);
            curl_setopt($session, CURLOPT_HTTPHEADER, $header);            

            $msg_chk    =   curl_exec($session);  
            $result     =   json_decode($msg_chk);
            $httpcode   =   curl_getinfo($session, CURLINFO_HTTP_CODE);  

            if($httpcode == 200 || $httpcode == 201 || $httpcode == 202)
            {
                return (object)array (
                    'success'   =>  true,
                    'data'      =>  $result
                );
            } else {
                return (object)array(
                    "success"   => false,
                    "message"   => $msg_chk
                );
                \Log::error('Error message Exception: '.$msg_chk);
            }            

        }
        catch(Exception  $ex)
        {
            $return = (object)array(
                "success"       => false ,
                "message"       => $ex->getMessage()
            );

            \Log::error($ex->getMessage() . $ex->getTraceAsString());

            return $return;
        }
    }

    /**
     * Remask document to CPF or CNPJ
     * @param String $document String document to remask
     * @return String formated or empty string
     */
    private static function remaskDocument(String $document)
    {
        if($document && strlen($document) >= 11 ) {
            $document = preg_replace('/[^0-9]/', '', $document);
            $cnpjMask = "%s%s.%s%s%s.%s%s%s/%s%s%s%s-%s%s";
            $cpfMask = "%s%s%s.%s%s%s.%s%s%s-%s%s";
            $mask = ((strlen($document)) > 11) ? $cnpjMask : $cpfMask;
            $remaskedDocument = vsprintf($mask, str_split($document));
            return $remaskedDocument;
        }
        return null;
    }

    public static function retrieveHooks($isPix = false)
    {
        $body       =   null;
        $header     =   self::getHeader(false, $isPix);
        $url        =   sprintf('%s/resources/webhooks?limit=1000', self::apiUrl());

        $apiRequest =   self::apiRequest($url, $body, $header, self::GET_REQUEST);

        return $apiRequest;
    }

    public static function registerHook($postbackUrl, $isPix = false)
    {
        $header     =   self::getHeader(false, $isPix);
        $url        =   sprintf('%s/resources/webhooks', self::apiUrl());

        $actions = [
            'TransactionCreated',
            'TransactionWaitingPayment',
            'TransactionCanceled',
            'TransactionPreAuthorized',
            'TransactionCaptured',
            'TransactionDenied',
            'TransactionDisputed',
            'TransactionChargedback'
        ];

        if($isPix) {
            $actions = [
                'PaymentLinkPaymentSucceeded',
                'PaymentLinkPaymentFailed',
                'SubscriptionPaymentSucceeded',
                'SubscriptionPaymentFailed',
                'ChargePaymentSucceeded',
                'ChargePaymentFailed',
                'TransactionCreated',
                'TransactionWaitingPayment',
                'TransactionCanceled',
                'TransactionPreAuthorized',
                'TransactionCaptured',
                'TransactionDenied',
                'TransactionDisputed',
                'TransactionChargedback',
                'TransferPaymentSucceeded',
                'TransferPaymentFailed'
            ];
        }

        $body       =   (object)array(
            'http_method'   =>  self::POST_REQUEST,
            'url'           =>  $postbackUrl,
            'description'   =>  'Webhook para receber notificações de atualização das transações',
            'actions'       =>  (object)$actions
        );

        $apiRequest =   self::apiRequest($url, json_encode($body), $header, self::POST_REQUEST);

        return $apiRequest;
    }

    public static function getMinStateString($state)
    {
        $minState = '';
        // get string full state and return min string
        switch (strtolower($state)) {
            case 'acre':
                $minState = 'AC';
                break;
            case 'alagoas':
                $minState = 'AL';
                break;
            case 'amapá':
                $minState = 'AP';
                break;
            case 'amazonas':
                $minState = 'AM';
                break;
            case 'bahia':
                $minState = 'BA';
                break;
            case 'ceará':
                $minState = 'CE';
                break;
            case 'distrito federal':
                $minState = 'DF';
                break;
            case 'espírito santo':
                $minState = 'ES';
                break;
            case 'goiás':
                $minState = 'GO';
                break;
            case 'maranhão':
                $minState = 'MA';
                break;
            case 'mato grosso':
                $minState = 'MT';
                break;
            case 'mato grosso do sul':
                $minState = 'MS';
                break;
            case 'minas gerais':
                $minState = 'MG';
                break;
            case 'pará':
                $minState = 'PA';
                break;
            case 'paraíba':
                $minState = 'PB';
                break;
            case 'paraná':
                $minState = 'PR';
                break;
            case 'pernambuco':
                $minState = 'PE';
                break;
            case 'piauí':
                $minState = 'PI';
                break;
            case 'rio de janeiro':
                $minState = 'RJ';
                break;
            case 'rio grande do norte':
                $minState = 'RN';
                break;
            case 'rio grande do sul':
                $minState = 'RS';
                break;
            case 'rondônia':
                $minState = 'RO';
                break;
            case 'roraima':
                $minState = 'RR';
                break;
        }
        return $minState;
    }
}
