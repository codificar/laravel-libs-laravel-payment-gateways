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
    const URL  =   "https://api.pagar.me/core/v5";

    const ROUND_VALUE   =   100;
    const APP_TIMEOUT   =   200;

    const POST_REQUEST  =   'POST';
    const GET_REQUEST   =   'GET';
    const PUT_REQUEST   =   'PUT';

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
     *
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
     *
     * @return Object      {
     *                      'success',
     *                      'data',
     *                      ?'message' //if it fails, with false success
     *                     }
     */
    public static function capture(Transaction $transaction, $amount)
    {
        $url = sprintf('%s/orders/%s/closed', self::URL, $transaction->gateway_transaction_id);

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
        return false;
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
        $url = sprintf('%s/orders/%s', self::URL, $transaction->gateway_transaction_id);

        $body       =   null;
        $header     =   self::getHeader(true);
        $retrieveRequest =  self::apiRequest($url, $body, $header, self::GET_REQUEST);

        return $retrieveRequest;
    }

    public static function refund(Transaction $transaction)
    {
        $url = sprintf('%s/orders/', self::URL, $transaction->gateway_transaction_id);

        $body       =   null;
        $header     =   self::getHeader(true);
        $refundRequest = self::apiRequest($url, $body, $header, self::POST_REQUEST);

        return $refundRequest;
    }

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

        if(!isset($response->data->id))
        {
            $url = sprintf('%s/recipients', self::URL);
            $verb = self::POST_REQUEST;
        }
        else
        {
            $url = sprintf('%s/recipients/%s', self::URL, $ledgerBankAccount->recipient_id);
            $verb = self::PUT_REQUEST;
        }

        $phoneRemask    =   null;
        $provider       =   Provider::find($ledgerBankAccount->provider_id);
        $bank           =   Bank::find($ledgerBankAccount->bank_id);

        //mobile or fixed phone remask BR
        if(preg_match('/^\d(\d{2})(\d{4})(\d{4})$/', substr($provider->phone,2),  $matches))
            $phoneRemask = "(".$matches[1].") " . $matches[2] . '-' . $matches[3];
        if(preg_match('/^\d(\d{2})(\d{5})(\d{4})$/', substr($provider->phone,2),  $matches))
            $phoneRemask = "(".$matches[1].") " . $matches[2] . '-' . $matches[3];
        if(!$phoneRemask)
            $phoneRemask = '(31) 99999-9999'; //if the phone has save error

        $fields = (object)array(
            'login'         =>  $provider->email,
            'password'      =>  preg_replace('/\D/', '', $provider->document),
            'name'          =>  $ledgerBankAccount->holder,
            'email'         =>  $provider->email,
            'phone'         =>  $phoneRemask,
            'bank'      =>  (object)array(
                'code'          =>  $bank->code,
                'agency'        =>  $ledgerBankAccount->agency,
                'account'       =>  $ledgerBankAccount->account.$ledgerBankAccount->account_digit
            )
        );

        if(!$response)
            $fields = array_merge((array)$fields, ['cpf_cnpj'=>self::remaskDocument(preg_replace('/\D/', '', $ledgerBankAccount->document))]); //document remask BR

        //to juridical bank account
        $birthday = $ledgerBankAccount->birthday_date;
        if(strlen($ledgerBankAccount->document) > 11)
            $fields['owner'] = (object)array(
                'name'      =>  $provider->first_name . $provider->last_name,
                'email'     =>  $provider->email,
                'cpf'       =>  self::remaskDocument(preg_replace('/\D/', '', $provider->document)), //document remask BR
                'phone'     =>  $phoneRemask,
                'birthdate' =>  strlen($birthday) == 10 ? $birthday : '1970-01-01' //if null birthday
            );

        $header     =   self::getHeader();
        $body       =   json_encode($fields);
        $accountRequest = self::apiRequest($url, $body, $header, $verb);

        // if(isset($accountRequest->data->attributes->is_active) && $accountRequest->data->attributes->is_active === false)
        //     $accountRequest = self::activeSeller($accountRequest->data->id);

        return $accountRequest;
    }

    public static function getSeller($sellerId)
    {
        $url = sprintf('%s/recipients/%s', self::URL, $sellerId);

        $body       =   null;
        $header     =   self::getHeader();
        $sellerRequest =   self::apiRequest($url, $body, $header, self::GET_REQUEST);

        return $sellerRequest;
    }

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

        if($response->success)
        {
            $result = (object)array(
                'success'           =>  true,
                'recipient_id'      =>  $response->data->id,
                'is_active'         =>  $response->data->attributes->is_active
            );
            $ledgerBankAccount->recipient_id = $response->data->id;
            $ledgerBankAccount->save();
        }
        else
        {
            #TODO remover após job de recriar recipients ao trocar gateway
            $newAccount = self::createOrUpdateAccount($ledgerBankAccount);
            if($newAccount->success)
            {
                $ledgerBankAccount->recipient_id = $newAccount->data->id;
                $ledgerBankAccount->save();
                $result = (object)array(
                    'success'       =>  true,
                    'recipient_id'  =>  $ledgerBankAccount->recipient_id,
                    'is_active'     =>  $newAccount->data->attributes->is_active
                );
            }
            else
            {
                $result = (object)array(
                    'success'       =>  false,
                    'recipient_id'  =>  ""
                );
            }
        }

        // if(isset($result->is_active) && $result->is_active === false)
        //     self::activeSeller($result['recipient_id']);

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
        $url = sprintf('%s/orders/', self::URL);

        $header     =   self::getHeader(true);
        $body       =   self::getBody(null, $amount, null, false, null, $client, $boletoExpirationDate);
        $billetRequest =   self::apiRequest($url, $body, $header, self::POST_REQUEST);

        return $billetRequest;
    }

    public static function pixCharge($amount, $user)
    {
        $url = sprintf('%s/orders/', self::URL);

        $header     =   self::getHeader(true);
        $body       =   self::getBody(null, $amount, null, true, null, $user, null, true);
        $pixRequest =   self::apiRequest($url, $body, $header, self::POST_REQUEST);

        return $pixRequest;
    }

    // public static function activeSeller($sellerId)
    // {
    //     $activeUrl       =   sprintf('%s/resources/sellers?id=%s', self::URL, $sellerId);

    //     $activeBody      =   json_encode(["is_active"=>true]);
    //     $activeHeader    =   self::getHeader();
    //     $activeRequest   =   self::apiRequest($activeUrl, $activeBody, $activeHeader, self::PUT_REQUEST);

    //     return $activeRequest;
    // }

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

    private static function getBody($payment = null, $amount, $providerAmount, $capture = false, Provider $provider = null, $client = null, $billetExpiry = null, $isPix = false)
    {
        if($payment)
            $client         =   User::find($payment->user_id);

        $cnpjMask           =   "%s%s.%s%s%s.%s%s%s/%s%s%s%s-%s%s";
        $cpfMask            =   "%s%s%s.%s%s%s.%s%s%s-%s%s";

        $mask               =   ((strlen($client->document)) > 11) ? $cnpjMask : $cpfMask;
        $personType         =   ((strlen($client->document)) > 11) ? 'company' : 'individual';

        $client->document   =   vsprintf($mask, str_split($client->document));
        $clientPhone        =   self::phoneDivide($client->phone);

        $orderId        =   self::getOrderId();
        $requestId      =   $client ? $client->getLastRequest()->id : $orderId;

        $fields = (object)array(
            "items"     => [
                (object)array(
                    "amount"        =>  $amount,
                    "quantity"      =>  1,
                    "description"   =>  substr((string) Settings::findByKey('pagarme_product_title'), 0, 254),
                    "code"          =>  "$requestId"
                )
            ],
            "customer"      =>  (object)array(
                "name"      =>  $client->first_name.' '.$client->last_name,
                "email"     =>  $client->email,
                "document"  =>  $client->document,
                "type"      =>  $personType,
                "phones"    =>  (object)array(
                    "home_phone"        =>  (object)array(
                        "country_code"  =>  $clientPhone['country_code'],
                        "number"        =>  $clientPhone['number'],
                        "area_code"     =>  $clientPhone['area_code']
                    )
                )
            ),
            "closed"        =>  true,
            "payments"      =>  null
        );

        if($payment)
        {
            $expirationDate = self::getExpirationDate($payment);

            $cardNumber = $payment->getCardNumber();

            $payFields = (object)array(
                "payment_method"    => "credit_card",
                "credit_card"       => (object)array(
                    "capture"               =>  $capture,
                    "installments"          =>  1,
                    "card"                  => (object)array(
                        "number"        =>  $cardNumber,
                        "holder_name"   =>  $payment->getCardHolder(),
                        "exp_month"     =>  $expirationDate[0],
                        "exp_year"      =>  str_pad($expirationDate[1], 2, '0', STR_PAD_LEFT),
                        "cvv"           =>  $payment->getCardCvc()
                    )
                )
            );
        }
        else if($isPix)
        {
            $payFields = (object)array(
                "payment_method" => "pix",
                "pix" => (object)array(
                    "expires_in" => "86400"
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
        }

        $fields->payments[] = $payFields;

        if($capture && $provider && isset($provider->id) && $payment)
        {
            $split = self::getSplitInfo($provider->id, $providerAmount, 'seller_id');
            $fields->split_rules = [$split];
        }

        return json_encode($fields);
    }

    // private static function getSplitInfo($providerId, $providerAmount, $sellerIndex)
    // {
    //     $ledgerBankAccount = LedgerBankAccount::findBy('provider_id', $providerId);

    //     if(!$ledgerBankAccount)
    //         return false;

    //     $sellerId = self::checkProviderAccount($ledgerBankAccount);

    //     if(!isset($sellerId->success) || (isset($sellerId->success) && !$sellerId->success))
    //         return false;

    //     $fields = (object)array(
    //         $sellerIndex            =>  $sellerId->recipient_id,
    //         'amount'                =>  floatval($providerAmount),
    //         'liable'                =>  true,
    //         'charge_processing_fee' =>  true
    //     );

    //     return $fields;
    // }

    private static function getHeader($useVersion = false)
    {
        $version    =   ['x-api-version: 2'];
        $token      =   self::makeToken();
        $ipagToken  =   Settings::findObjectByKey('ipag_token');
        $basic      =   $ipagToken && isset($ipagToken->value)  ? $ipagToken->value : $token;

        $header = array (
            'Content-Type: application/json',
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
            Log::error($ex->getMessage().$ex->getTraceAsString());
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

    private static function remaskDocument($document)
    {
        $cnpjMask = "%s%s.%s%s%s.%s%s%s/%s%s%s%s-%s%s";
        $cpfMask = "%s%s%s.%s%s%s.%s%s%s-%s%s";
        $mask = ((strlen($document)) > 11) ? $cnpjMask : $cpfMask;

        return vsprintf($mask, str_split($document));
    }

    private static function phoneDivide($phone)
	{
        $numeralPhone = preg_replace('/\D/', '', $phone);
        $objPhone['area_code'] = substr($numeralPhone, -11, -9);
        $objPhone['number'] = substr($numeralPhone, -9);
        $objPhone['country_code'] = str_replace($objPhone['area_code'].$objPhone['number'], '', $numeralPhone);

		return $objPhone;
	}

    public static function retrieveHooks()
    {
        $body       =   null;
        $header     =   self::getHeader();
        $url        =   sprintf('%s/resources/webhooks', self::URL);
        $apiRequest =   self::apiRequest($url, $body, $header, self::GET_REQUEST);

        return $apiRequest;
    }

    public static function registerHook($postbackUrl)
    {
        $header     =   self::getHeader();
        $url        =   sprintf('%s/resources/webhooks', self::URL);

        $body       =   (object)array(
            'http_method'   =>  self::POST_REQUEST,
            'url'           =>  $postbackUrl,
            'description'   =>  'Webhook para receber notificações de atualização das transações',
            'actions'       =>  (object)array(
                'TransactionCreated',
                'TransactionWaitingPayment',
                'TransactionCanceled',
                'TransactionPreAuthorized',
                'TransactionCaptured',
                'TransactionDenied',
                'TransactionDisputed',
                'TransactionChargedback'
            )
        );

        $apiRequest =   self::apiRequest($url, json_encode($body), $header, self::POST_REQUEST);

        return $apiRequest;
    }
}
