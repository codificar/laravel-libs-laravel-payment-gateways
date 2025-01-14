<?php

namespace Codificar\PaymentGateways\Libs;
use Carbon\Carbon;

//models do sistema
use Log, Exception;
use Payment;
use Provider;
use Codificar\Finance\Models\Transaction;
use User;
use LedgerBankAccount;
use Settings;
use Bank;
use Codificar\PaymentGateways\Libs\handle\phone\PhoneNumber;

class PagarmeApi
{
    const URL  =   "https://api.pagar.me/core/v5";

    const ROUND_VALUE   =   100;
    const APP_TIMEOUT   =   200;

    const POST_REQUEST  =   'POST';
    const GET_REQUEST   =   'GET';
    const PUT_REQUEST   =   'PUT';
    const DELETE_REQUEST=   'DELETE';

    /**
     * Authorizes a card payment, reserving service amount
     *
     * @param Object       $payment     Object that represents a card on system.
     * @param Object       $user        Object that represents a user on system.
     * @param Float        $amount      Total value of service.
     * @param Boolean      $capture     Informs if charge have immediate capture.
     * @return Object      {
     *                      'success',
     *                      'data',
     *                      ?'message' //if it fails, with false success
     *                     }
     */
    public static function charge(Payment $payment, User $user, $amount, $capture)
    {
        $url = sprintf('%s/orders/', self::URL);

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
     * @return Object      {
     *                      'success',
     *                      'data',
     *                      ?'message' //if it fails, with false success
     *                     }
     */
    public static function chargeWithOrNotSplit(Payment $payment, Provider $provider = null, $amount, $providerAmount = null, $capture)
    {
        $url = sprintf('%s/orders/', self::URL);

        $header     =   self::getHeader(true);
        $body       =   self::getBody($payment, $amount, $providerAmount, $capture, $provider);
        $chargeSplitRequest =   self::apiRequest($url, $body, $header, self::POST_REQUEST);

        return $chargeSplitRequest;
    }

    /**
     * Captures a authorized charge by ID
     *
     * @param Object       $transaction     Object that represents a transaction on system.
     * @return Object      {
     *                      'success',
     *                      'data',
     *                      ?'message' //if it fails, with false success
     *                     }
     */
    public static function capture(Transaction $transaction, $amount)
    {
        $url = sprintf('%s/charges/%s/capture', self::URL, $transaction->gateway_transaction_id);

        $body       =   $amount ? array('amount' => self::amountRound($amount)) : null;
        $header     =   self::getHeader(true);
        $captureRequest =   self::apiRequest($url, $body, $header, self::POST_REQUEST);

        return $captureRequest;
    }

    /**
     * Captures a authorized charge by ID, using split rules
     *
     * @param Object       $transaction     Object that represents a transaction on system.
     * @param Object       $provider        Object that represents a provider on system.
     * @return Object      {
     *                      'success',
     *                      'data',
     *                      ?'message' //if it fails, with false success
     *                     }
     */
    public static function captureWithSplit(Transaction $transaction, Provider $provider, $providerAmount)
    {
        $splitBody      =   self::getSplitInfo($provider->id, $providerAmount, $transaction->gross_value);
        $splitHeader    =   self::getHeader();
        $splitUrl       =   sprintf('%s/charges/%s/capture', self::apiUrl(), $transaction->gateway_transaction_id);
        $captureSplitRequest =  self::apiRequest($splitUrl, $splitBody, $splitHeader, self::POST_REQUEST);

        return $captureSplitRequest;
    }

    /**
     * Retrives a gateway transaction by ID
     *
     * @param Object       $transaction     Object that represents a transaction on system.
     * @return Object      {
     *                      'success',
     *                      'data',
     *                      ?'message' //if it fails, with false success
     *                     }
     */
    public static function retrieve(Transaction $transaction)
    {
        $url = sprintf('%s/charges/%s', self::URL, $transaction->gateway_transaction_id);

        $body       =   null;
        $header     =   self::getHeader(true);
        $retrieveRequest =  self::apiRequest($url, $body, $header, self::GET_REQUEST);

        return $retrieveRequest;
    }

    /**
     * Retrives a gateway transaction by ID
     *
     * @param Transaction $transaction 
     * @return Object      {
     *                      'success',
     *                      'data',
     *                      ?'message' //if it fails, with false success
     *                     }
     */
    public static function refund(Transaction $transaction)
    {
        $url = sprintf('%s/charges/%s', self::URL, $transaction->gateway_transaction_id);

        $header        =    self::getHeader(true);
        $refundRequest =    self::apiRequest($url, null, $header, self::DELETE_REQUEST);

        return $refundRequest;
    }

    /**
     * Create or update your bank account
     *
     * @param LedgerBankAccount     $ledgerBankAccount
     * @return Object               {
     *                                  'success',
     *                                  'data',
     *                                  ?'message' //if it fails, with false success
     *                              }
     */
    public static function createOrUpdateAccount(LedgerBankAccount $ledgerBankAccount)
    {
        if(
            $ledgerBankAccount->recipient_id != '' && 
            $ledgerBankAccount->recipient_id != 'empty' && 
            $ledgerBankAccount->recipient_id !== null
        )
            $response = self::getSeller($ledgerBankAccount->recipient_id);
        else
            $response = null;

        if(!isset($response->id))
        {
            $url = sprintf('%s/recipients', self::URL);
            $verb = self::POST_REQUEST;
        }
        else
        {
            $url = sprintf('%s/recipients/%s', self::URL, $ledgerBankAccount->recipient_id);
            $verb = self::PUT_REQUEST;
        }

        $provider       =   Provider::find($ledgerBankAccount->provider_id);
        $bank           =   Bank::find($ledgerBankAccount->bank_id);

        $providerType   =   ((strlen($provider->document)) > 11) ? 'company' : 'individual';
        $ledgerType     =   ((strlen($ledgerBankAccount->document)) > 11) ? 'company' : 'individual';

        $fields = (object)array(
            "name"      => $provider->first_name . $provider->last_name,
            "email"     => $provider->email,
            "document"  => $provider->document,
            "type"      => $providerType,
            "default_bank_account"      =>  (object)array(
                "holder_name"           =>  $ledgerBankAccount->holder,
                "holder_type"           =>  $ledgerType,
                "holder_document"       =>  $ledgerBankAccount->document,
                "bank"                  =>  $bank->code,
                "branch_number"         =>  $ledgerBankAccount->agency,
                "branch_check_digit"    =>  $ledgerBankAccount->agency_digit,
                "account_number"        =>  $ledgerBankAccount->account,
                "account_check_digit"   =>  $ledgerBankAccount->account_digit,
                "type"                  =>  ($ledgerBankAccount->account_type == "conta_corrente" ? "checking" : ($ledgerBankAccount->account_type == "conta_poupanca" ? "savings" : ""))
            ),
            "transfer_settings"     =>  (object)array(
                "transfer_enabled"    =>  (Settings::findByKey('auto_transfer_provider_payment') == '1') ?? false,
                "transfer_interval"   =>  "Daily",
                "transfer_day"        =>  0
            )
        );

        $header         =   self::getHeader();
        $body           =   json_encode($fields);
        $accountRequest =   self::apiRequest($url, $body, $header, $verb);

        if(isset($accountRequest->status) && $accountRequest->status == 'active')
            $accountRequest = $response = (object)array(
                'success'       =>  false,
                'recipient_id'  =>  ""
            );

        return $accountRequest;
    }

    /**
     * Get the recipient data
     *
     * @param  Integer     $sellerId
     * @return Object      {
     *                       'success',
     *                       'data',
     *                       ?'message' //if it fails, with false success
     *                     }
     */
    public static function getSeller($sellerId)
    {
        $url = sprintf('%s/recipients/%s', self::URL, $sellerId);

        $body       =   null;
        $header     =   self::getHeader();
        $sellerRequest =   self::apiRequest($url, $body, $header, self::GET_REQUEST);

        return $sellerRequest;
    }

    /**
	 * Função para gerar boletos de pagamentos
     * 
	 * @param LedgerBankAccount $ledgerBankAccount
     * @return Object      {
     *                      'success',
     *                      'recipient_id',
     *                      ?'is_active'
     *                     }
	 */
    public static function checkProviderAccount(LedgerBankAccount $ledgerBankAccount)
    {
        $sellerId = $ledgerBankAccount->recipient_id;

        if($sellerId == '' || $sellerId == 'empty' || $sellerId === null)
            $response = self::createOrUpdateAccount($ledgerBankAccount);
        else
            $response = self::getSeller($sellerId);

        if(!isset($response->data->id))
        {
            Log::error("Retrieve/create recipient fail: " . print_r($response, 1));
            $response = (object)array(
                'success'       =>  false,
                'recipient_id'  =>  ""
            );
        }

        $result = (object)array(
            'success'           =>  true,
            'recipient_id'      =>  $response->data->id,
            'is_active'         =>  ($response->data->status == 'active') ?? false
        );
        $ledgerBankAccount->recipient_id = $response->data->id;
        $ledgerBankAccount->save();
        return $result;
    }

    /**
	 * Function to generate payment slips
     * 
	 * @param Decimal $amount valor do boleto
	 * @param User/Provider $client instância do usuário ou prestador
	 * @param String $boletoExpirationDate data de expiração do boleto
     * @return Object      {
     *                      'success',
     *                      'data',
     *                      ?'message' //if it fails, with false success
     *                     }
     */
    public static function billetCharge($amount, $client, $boletoExpirationDate)
    {
        $url = sprintf('%s/orders/', self::URL);

        $header     =   self::getHeader();
        $body       =   self::getBody(null, $amount, null, false, null, $client, $boletoExpirationDate);
        $billetRequest =   self::apiRequest($url, $body, $header, self::POST_REQUEST);

        return $billetRequest;
    }

    /**
	 * Make a charge with pix
     * 
	 * @param Decimal   $amount valor do boleto
	 * @param User/Provider $client instância do usuário ou prestador
     * @return Object      {
     *                      'success',
     *                      'data',
     *                      ?'message' //if it fails, with false success
     *                     }
     */
    public static function pixCharge($amount, $user, $providerAmount = null, $provider = null)
    {
        $url = sprintf('%s/orders/', self::URL);

        $header     =   self::getHeader(true);
        $body       =   self::getBody(null, $amount, $providerAmount, true, $provider, $user, null, true, false);
        $pixRequest =   self::apiRequest($url, $body, $header, self::POST_REQUEST);

        return $pixRequest;
    }

    /**
	 * Make a charge with debit
     * 
     * @param Payment   $payment
	 * @param Decimal   $amount valor do boleto
     * @return Object      {
     *                      'success',
     *                      'data',
     *                      ?'message' //if it fails, with false success
     *                     }
     */
    public static function debit(Payment $payment, $amount)
    {
        $url = sprintf('%s/orders/', self::URL);

        $header         =   self::getHeader(true);
        $body           =   self::getBody($payment, $amount, null, false, null, null, null, false, true);
        $debitRequest   =   self::apiRequest($url, $body, $header, self::POST_REQUEST);

        return $debitRequest;
    }

    /**
	 * Get OrderId
     * 
     * @return String   $orderId
     */
    private static function getOrderId()
    {
        list($microSeconds, $seconds) = explode(" ", microtime());
        $orderId = $seconds.substr($microSeconds, 2, -3);

        return $orderId;
    }

    /**
	 * Get the expiration date
     * 
     * @param Payment   $orderId
     * @return String   $expDate
     */
    private static function getExpirationDate($payment)
    {
        $month = $payment->getCardExpirationMonth();
        $year = $payment->getCardExpirationYear();

        $expDate[0] = str_pad($month, 2, '0', STR_PAD_LEFT);
        $year  = $year % 100;
        $expDate[1] = (strlen($year) < 4) ? '20'.$year : $year ;

        return $expDate;
    }

    /**
	 * Do the math of the amountround
     * 
     * @param  Decimal   $amount
     * @return Decimal   $amount
     */
    public static function amountRound($amount)
    {
        $amountValue = (float)$amount; 
        $amountValue = $amountValue * self::ROUND_VALUE;
        $amountValue = (int)$amountValue;

        return $amountValue;
    }

    /**
	 * Get the body information
     * 
     * @param  Payment   $payment
     * @param  Decimal   $amount
     * @param  Decimal   $providerAmount
     * @param  Boolean   $capture
     * @param  Provider  $provider
     * @param  User      $client
     * @param  String    $billetExpiry
     * @param  Boolean   $isPix
     * @param  Boolean   $isDebit
     * @return Json
     */
    private static function getBody($payment = null, $amount, $providerAmount = null, $capture = false, Provider $provider = null, $client = null, $billetExpiry = null, $isPix = false, $isDebit = false)
    {
        $paymentDocument = null;
        if($payment)
        {
            if(!$client) {
                $client = $payment->user_id != null ? User::find($payment->user_id) : Provider::find($payment->provider_id);

            }
            $paymentType = $isDebit ? 'debit_card' : 'credit_card';
            if($payment->document){
                $paymentDocument = $payment->document;
            }
        }

        if($client && !$paymentDocument) {
            $paymentDocument = $client->document;
        }

        $document = $paymentDocument ? preg_replace( '/[^0-9]/', '', $paymentDocument ) : '';

        $personType     =   ((strlen($paymentDocument)) > 11) ? 'company' : 'individual';
        $orderId        =   self::getOrderId();

        try {
            $phoneLib = new PhoneNumber($client->phone);
        } catch (\Exception $e) {
            \Log::error($e->getMessage() . $e->getTraceAsString());
        }
        

        $fields = (object)array(
            "items"     => [
                (object)array(
                    "amount"        =>  self::amountRound($amount),
                    "quantity"      =>  1,
                    "description"   =>  substr((string) Settings::findByKey('gateway_product_title'), 0, 254),
                    "code"          =>  "$orderId"
                )
            ],
            "customer"      =>  (object)array(
                "name"      =>  $client->first_name.' '.$client->last_name,
                "email"     =>  $client->email,
                "document"  =>  $document,
                "type"      =>  $personType,
                "phones"    =>  (object)array(
                    "home_phone"        =>  (object)array(
                        "country_code"  =>  $phoneLib->getDDI(),
                        "number"        =>  $phoneLib->getPhoneNumber(),
                        "area_code"     =>  $phoneLib->getDDD()
                    )
                )
            ),
            "closed"        =>  true
        );

        if($payment)
        {
            $expirationDate = self::getExpirationDate($payment);

            $cardNumber = $payment->getCardNumber();

            $address = [
                "zip_code" => $client->zipcode,
                "city" => $client->address_city,
                "state" => $client->state,
                "country" => self::countryInitials($client->country),
                "line_1" => "$client->address_number, $client->address, $client->address_neighbour"
            ];

            $payFields = (object)array(
                "payment_method"    => "$paymentType",
                "$paymentType"      => (object)array(
                    "card"              => (object)array(
                        "number"        =>  $cardNumber,
                        "holder_name"   =>  $payment->getCardHolder(),
                        "exp_month"     =>  $expirationDate[0],
                        "exp_year"      =>  str_pad($expirationDate[1], 2, '0', STR_PAD_LEFT),
                        "cvv"           =>  $payment->getCardCvc(),
                        "billing_address" => $address
                    )
                )
            );

            if(!$isDebit)
                $payFields->credit_card = (object)array_merge(
                    (array)$payFields->credit_card, 
                    array("capture"   =>  $capture, "installments"  =>  1)
                );

        }
        else if($isPix)
        {
            $payFields = (object)array(
                "payment_method" => "pix",
                "pix" => (object)array(
                    "expires_in" => "86400" //24h
                )
            );
        }
        else
        {
            $payFields = (object)array(
                "payment_method"    =>  "boleto",
                "boleto"            =>  (object)array(
                    "instructions"      =>  Settings::getBilletInstructions(),
                    "due_at"            =>  Carbon::parse($billetExpiry)->format('Y-m-d H:i:s'),
                    "document_number"   =>  "$orderId",
                    "type"              =>  "DM" //DM (Duplicata Mercantil) | BDP (Boleto de proposta)
                )
            );

            $fields->customer->address  =   (object)array(
                "line_1"    => "$client->address_number, $client->address, $client->address_neighbour",
                "line_2"    => $client->address_complements,
                "zip_code"  => preg_replace('/\D/', '', $client->zipcode),
                "city"      => $client->address_city,
                "state"     => $client->state,
                "country"   => self::countryInitials($client->country)
            );
        }

        $fields->payments[] = $payFields;
        if($provider && isset($provider->id) && ($payment || $isPix))
        {
            $splitFields = self::getSplitInfo($provider->id, $providerAmount, $amount);
            $fields->payments[0]->split = $splitFields->split ?? [];
        }
        return json_encode($fields);
    }

    /**
	 * Get the split information
     * 
     * @param  Integer   $providerId
     * @param  Decimal   $totalAmount
     * @param  Decimal   $totalAmount
     * @return Object      {
     *                      'amount',
     *                      'recipient_id',
     *                      'type',
     *                      'options'{
     *                          'charge_processing_fee'
     *                          'charge_remainder_fee'
     *                          'liable'
     *                      }
     *                     }
     */
    private static function getSplitInfo($providerId, $providerAmount, $totalAmount)
    {
        $ledgerBankAccount = LedgerBankAccount::where('provider_id', $providerId)->first();

        if(!$ledgerBankAccount)
            return false;

        $sellerId   =   self::checkProviderAccount($ledgerBankAccount);

        if(!isset($sellerId->success) || (isset($sellerId->success) && !$sellerId->success))
            return false;

        $charge_processing_fee = boolval(Settings::findByKey('gateway_split_taxes', false));
        $liable = boolval(Settings::findByKey('liable_split', false));
        $charge_remainder_fee = boolval(Settings::findByKey('charge_remainder_fee', false));
        $splitFields     =   (object)array(
            "split" =>  [
                (object)array(
                    "amount"        =>  self::amountRound($totalAmount) - self::amountRound($providerAmount),
                    "recipient_id"  =>  Settings::findByKey('pagarme_recipient_id'),
                    "type"          =>  "flat", //flat | percentage
                    "options"       =>  (object)array(
                        "charge_processing_fee" =>  true,
                        "charge_remainder_fee"  =>  true,
                        "liable"                =>  true
                    )
                ),
                (object)array(
                    "amount"        =>  self::amountRound($providerAmount),
                    "recipient_id"  =>  $sellerId->recipient_id,
                    "type"          =>  "flat", //flat | percentage
                    "options"       =>  (object)array(
                        "charge_processing_fee" =>  $charge_processing_fee,
                        "charge_remainder_fee"  =>  $charge_remainder_fee,
                        "liable"                =>  $liable
                    )
                )
            ]
        );
        return $splitFields;
    }

    /**
	 * Prepare the header
     * 
     * @return Array
     */
    private static function getHeader()
    {
        $token          =   self::makeToken();
        $pagarmeToken   =   Settings::findObjectByKey('pagarme_token');
        $basic          =   $pagarmeToken && isset($pagarmeToken->value) ? $pagarmeToken->value : $token;

        $header = array (
            'Content-Type: application/json',
            'Authorization: Basic '.$basic
        );

        return $header;
    }

    /**
	 * Prepare the header
     * 
     * @return String   $concateString
     */
    private static function makeToken()
    {
        $pagarmeSecret  =   Settings::findObjectByKey('pagarme_secret_key');

        $concateString  =   base64_encode($pagarmeSecret->value.':');

        try
        {
            $token = Settings::findObjectByKey('pagarme_token');
            $token->value = $concateString;
            $token->save();
        }
        catch (Exception $ex){
            Log::error($ex->getMessage().$ex->getTraceAsString());
        }

        return $concateString;
    }

    /**
	 * Make a structure to send a request
     * 
     * @param  String    $url
     * @param  Array     $fields
     * @param  Array     $header
     * @param  String    $requestType
     * @return Object      {
     *                      'success',
     *                      'message'
     *                     }
     */
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
                Log::error('Error message Exception: '.$msg_chk);
            }            

        }
        catch(Exception  $ex)
        {
            $return = (object)array(
                "success"       => false ,
                "message"       => $ex->getMessage()
            );

            Log::error(($ex));

            return $return;
        }
    }

    /**
	 * Prepare the header
     * @param  String   $country
     * @return String   $initials
     */
    private static function countryInitials($country)
    {
        switch(strtolower($country))
        {
            case 'angola':
            case 'ao':
                $initials = 'AO';
                break;
            case 'united states':
            case 'estados unidos':
            case 'us':
            case 'usa':
                $initials = 'US';
                break;
            case 'portugal':
            case 'pt':
                $initials = 'PO';
                break;
            default:
                $initials = 'BR';	
                break;
        }

        return $initials;
    }

    /**
	 * Retrive billet or pix date
     * 
     * @return Object      {
     *                      'success',
     *                      'data',
     *                      ?'message' //if it fails, with false success
     *                     }
     */
    public static function retrieveHooks()
    {
        $body       =   null;
        $header     =   self::getHeader();
        $url        =   sprintf('%s/hooks', self::URL);
        $apiRequest =   self::apiRequest($url, $body, $header, self::GET_REQUEST);

        return $apiRequest;
    }

}
