<?php

namespace Codificar\PaymentGateways\Libs;

use Carbon\Carbon;

use Codificar\PaymentGateways\Libs\IpagApi;

use ApiErrors;
use Exception;
//models do sistema
use Payment;
use Provider;
use Transaction;
use User;
use LedgerBankAccount;
use Requests;
use Settings;
// use RequestCharging;
use Log;

class IpagLib implements IPayment
{
    /**
     * Payment status code
     */
    public const CODE_CREATED          =   1;
    public const CODE_WAITING_PAYMENT  =   2;
    public const CODE_CANCELED         =   3;
    public const CODE_IN_ANALISYS      =   4;
    public const CODE_PRE_AUTHORIZED   =   5;
    public const CODE_PARTIAL_CAPTURED =   6;
    public const CODE_DECLINED         =   7;
    public const CODE_CAPTURED         =   8;
    public const CODE_CHARGEDBACK      =   9;
    public const CODE_IN_DISPUTE       =   10;

    /**
     * Payment status string
     */
    public const PAYMENT_NOTFINISHED  =   'not_finished';
    public const PAYMENT_AUTHORIZED   =   'authorized';
    public const PAYMENT_PAID         =   'paid';
    public const PAYMENT_DENIED       =   'denied';
    public const PAYMENT_VOIDED       =   'voided';
    public const PAYMENT_REFUNDED     =   'refunded';
    public const PAYMENT_PENDING      =   'pending';
    public const PAYMENT_ABORTED      =   'aborted';
    public const PAYMENT_SCHEDULED    =   'scheduled';

    public const WAITING_PAYMENT = 'waiting_payment';

    /**
     * ERROR MESSAGES
     */
    public const CUSTOMER_BLACK_LIST = 'Customer has been blacklisted';
    public const DECLINED = 'DECLINED';

    /**
     * Charge a credit card with split rules
     *
     * @param Payment       $payment
     * @param Provider      $provider
     * @param Decimal       $totalAmount        A positive decimal representing how much to charge
     * @param Decimal       $providerAmount
     * @param String        $description        An arbitrary string which you can attach to describe a Charge object
     * @param Boolean       $capture            Whether to immediately capture the charge. When false, the charge issues an authorization (or pre-authorization), and will need to be captured later.
     * @param User          $user               The customer that will be charged in this request
     *
     * @return Array ['success', 'status', 'captured', 'paid', 'transaction_id']
     */
    public function chargeWithSplit(Payment $payment, Provider $provider, $totalAmount, $providerAmount, $description, $capture = true, User $user = null)
    {
        try {
            $response = IpagApi::chargeWithOrNotSplit($payment, $provider, $totalAmount, $providerAmount, $capture);
            $response = HandleResponseIpag::handle($response);

            if (!$response['success']) {
                return $response;
            }

            $response = $response['data'];

            $sysAntifraud = Settings::findByKey('ipag_antifraud');
            $isAttributes = isset($response->attributes) && !empty($response->attributes);
            $isStatusGeneral = $isAttributes && isset($response->attributes->status) && !empty($response->attributes->status);
            $isStatusAntiFraud = $isAttributes && isset($response->attributes->antifraud->status) && !empty($response->attributes->antifraud->status);
            $isApprovedAntiFraud = $isStatusAntiFraud && strtolower($response->attributes->antifraud->status) == 'approved';
            $isCaptured = $isStatusGeneral && strtoupper($response->attributes->status->message) == 'CAPTURED';
            $isPreAuthorized = $isStatusGeneral && strtoupper($response->attributes->status->message) == 'PRE-AUTHORIZED';

            if (($sysAntifraud && $isApprovedAntiFraud) || (!$sysAntifraud)
                &&
                ($isCaptured || $isPreAuthorized)
            ) {
                $statusMessage = $response->attributes->status->message;
                $result = array(
                    'success' 		    => true,
                    'status' 		    => $statusMessage == 'CAPTURED' ? 'paid' : 'authorized',
                    'captured' 			=> $statusMessage == 'CAPTURED' ? true : false,
                    'paid' 		        => $statusMessage == 'CAPTURED' ? 'paid' : 'denied',
                    'transaction_id'    => (string)$response->id
                );
                return $result;
            } else {
                return array(
                    "success" 	=> false ,
                    'data' 		=> null,
                    'error' 	=> array(
                        "code" 		=> ApiErrors::CARD_ERROR,
                        "messages" 	=> array(trans('creditCard.customerCreationFail'))
                    )
                );
            }
        } catch (Exception $th) {
            return array(
                "success"           =>  false ,
                'data'              =>  null,
                'transaction_id'    =>  '',
                'error'     => array(
                    "code"          => ApiErrors::CARD_ERROR,
                    "messages"      => array(trans('creditCard.customerCreationFail'))
                )
            );
        }
    }

    /**
     * Charge a credit card
     *
     * @param Payment       $payment
     * @param Decimal       $totalAmount        A positive decimal representing how much to charge
     * @param String        $description        An arbitrary string which you can attach to describe a Charge object
     * @param Boolean       $capture            Whether to immediately capture the charge. When false, the charge issues an authorization (or pre-authorization), and will need to be captured later.
     * @param User          $user               The customer that will be charged in this request
     *
     * @return Array ['success', 'status', 'captured', 'paid', 'transaction_id']
     */
    public function charge(Payment $payment, $amount, $description, $capture = true, User $user = null)
    {
        try {
            $response = IpagApi::chargeWithOrNotSplit($payment, null, $amount, null, $capture);
            $sysAntifraud = filter_var(Settings::findByKey('ipag_antifraud'), FILTER_VALIDATE_BOOLEAN);
            $response = HandleResponseIpag::handle($response);

            if (!$response['success']) {
                return $response;
            }

            $response = $response['data'];

            //verifica se o sistema antifraude está ativo e se houve retorno aprovado via ipag
            $isAntiFraudApproved = $sysAntifraud &&
                isset($response->attributes->antifraud) &&
                $response->attributes->antifraud->status == 'approved';

            $isStatus = isset($response->attributes->status) && !empty($response->attributes->status);
            $statusMessage = "";
            if ($isStatus) {
                $statusMessage =  $response->attributes->status->message;
            }

            $isApproved = $statusMessage == 'CAPTURED' || $statusMessage == 'PRE-AUTHORIZED';

            if ($isApproved && ($isAntiFraudApproved || !$sysAntifraud)) {
                return array(
                    'success'           =>  true,
                    'captured'          =>  $statusMessage == 'CAPTURED' ? true : false,
                    'paid'              =>  $statusMessage == 'CAPTURED' ? true : false,
                    'status'            =>  $statusMessage == 'CAPTURED' ? 'paid' : 'authorized',
                    'transaction_id'    =>  (string)$response->id
                );
            } else {
                \Log::info('IPagLib > charge > Error Transaction:' . json_encode($response));
                $code = ApiErrors::CARD_ERROR;
                $message = array(trans('creditCard.customerCreationFail'));
                if (isset($response->message)) {
                    try {
                        $jsonErrors = json_decode($response->message);
                        $message = $jsonErrors->error->message;
                        $code = $jsonErrors->error->code;
                    } catch (Exception $e) {
                        if (gettype($response->message) == 'string') {
                            $message = $response->message;
                        }
                    }
                } elseif (isset($statusMessage)) {
                    $message = $statusMessage;
                }

                return array(
                    "success"           => false ,
                    'data'              => null,
                    'transaction_id'    => -1,
                    'error' => array(
                        "code"      => $code,
                        "messages"  => $this->getTranslateMessage($message)
                    )
                );
            }
        } catch (Exception $th) {
            \Log::info('IPagLib > charge > Exception: ' . $th->getMessage());
            \Log::error($th);

            $transaction_id = null;
            $isResponse = isset($response) && !empty($response);
            $isPayment = $isResponse && isset($response->Payment) && !empty($response->Payment);

            if ($isPayment) {
                $transaction_id = $response->Payment->PaymentId
                    ? $response->Payment->PaymentId
                    : null;
            }

            return array(
                "success"           =>  false ,
                'data'              =>  null,
                'transaction_id'    =>  $transaction_id,
                'error' => array(
                    "code"      =>  ApiErrors::CARD_ERROR,
                    "messages"  =>  array(trans('creditCard.customerCreationFail'))
                )
            );
        }
    }

    public function retrieveWebhooks($isPix = false)
    {
        try {
            $response = IpagApi::retrieveHooks($isPix);
            $response = HandleResponseIpag::handle($response);

            if (!$response['success']) {
                return $response;
            }

            $host = url('/');
            $webhooks = $this->getHostWebhooks($response['data']->data, $host, $isPix);

            return array(
                'success' 		 => true,
                'webhooks' 		 => $webhooks,
                'message' 		 => trans('payment.webhook_created')
            );
        } catch (\Throwable $th) {
            \Log::error($th->__toString());

            return array(
                "success" 	=> false ,
                'webhooks' 	=> [],
                'error' 	=> array(
                    "code" 		=> ApiErrors::API_ERROR,
                    "messages" 	=> array(trans('payment.webhook_error'))
                )
            );
        }
    }

    public function createPixWebhooks()
    {
        try {
            $url = route('GatewayPostbackPix') . '/ipag';
            $exists = false;
            $response = IpagApi::retrieveHooks(true);
            $response = HandleResponseIpag::handle($response);

            if (!$response['success']) {
                return $response;
            }

            $webhooks = $response['data']->data;

            if (gettype($webhooks) == 'array') {
                foreach ($webhooks as $webhook) {
                    if ($webhook->attributes->url == $url) {
                        $exists = true;
                        break;
                    }
                }
            }

            if ($exists) {
                return array(
                    'success' 		 => true,
                    'status' 		 => trans('payment.webhook_exists')
                );
            }

            $response = IpagApi::registerHook($url, true);
            $response = HandleResponseIpag::handle($response);

            if (!$response['success']) {
                return $response;
            }

            return array(
                'success' 		 => true,
                'status' 		 => trans('payment.webhook_created')
            );
        } catch (\Throwable $th) {
            \Log::error($th->__toString());

            return array(
                "success" 	=> false ,
                'data' 		=> null,
                'error' 	=> array(
                    "code" 		=> ApiErrors::CARD_ERROR,
                    "messages" 	=> array(trans('payment.webhook_error'))
                )
            );
        }
    }

    /**
     * Função para gerar boletos de pagamentos
     * @param int $amount valor do boleto
     * @param User/Provider $client instância do usuário ou prestador
     * @param string $postbackUrl url para receber notificações do status do pagamento
     * @param string $billetExpirationDate data de expiração do boleto
     * @param string $billetInstructions descrição no boleto
     * @return array
     */
    public function billetCharge($amount, $client, $postbackUrl, $billetExpirationDate, $billetInstructions)
    {
        try {
            //it's a system var, adm don't changes
            $responseHooks = IpagApi::retrieveHooks();
            $responseHooks = HandleResponseIpag::handle($responseHooks);

            if (!$responseHooks['success']) {
                return $responseHooks;
            }

            $webhooks = $this->getHostWebhooks($responseHooks['data']->data, $postbackUrl);

            if (empty($webhooks) || !isset($webhooks)) {
                $responseHooks = IpagApi::registerHook($postbackUrl);//criates a new hook
                $responseHooks = HandleResponseIpag::handle($responseHooks);

                if (!$responseHooks['success']) {
                    return $responseHooks;
                }

                $objectHook = Settings::findObjectByKey('ipag_webhook_isset');
                $objectHook->value = 1;
                $objectHook->save();
            }

            $response = IpagApi::billetCharge($amount, $client, $billetExpirationDate, $billetInstructions);
            $response = HandleResponseIpag::handle($response);

            if (!$response['success']) {
                return $response;
            }

            $response = $response['data'];
            $boleto = $response->attributes->boleto;

            return array(
                'success'                   =>  true,
                'captured'                  =>  true,
                'paid'                      =>  false,
                'status'                    =>  self::WAITING_PAYMENT,
                'transaction_id'            =>  (string)$response->id,
                'billet_url'                =>  $boleto->link,
                'digitable_line'            =>  $boleto->digitable_line,
                'billet_expiration_date'    =>  $boleto->due_date
            );
        } catch (\Throwable $th) {
            \Log::error($th->getMessage());

            return array(
                "success" 				=> false ,
                "type" 					=> 'api_charge_error' ,
                "code" 					=> '',
                "message" 				=> $th->getMessage(),
                "transaction_id"		=> ''
            );
        }
    }

    /**
     * @param array $webhooks
     * @param string $searchUrl string will be used as search url for webhooks
     * @return array array of webhook by hostname
     */
    private function getHostWebhooks(array $webhooks, string $searchUrl = '', $isPix = false)
    {
        if (!isset($searchUrl) || empty($searchUrl)) {
            $searchUrl = url('/');
        }

        if (gettype($webhooks)  == 'array') {
            $webhooks = array_filter($webhooks, function ($webhook) use ($searchUrl, $isPix) {
                if (isset($webhook->attributes->url)
                    && strpos($webhook->attributes->url, $searchUrl) !== false
                ) {

                    // retorna as urls que contenha o nome pix
                    $isUrlPix = strpos($webhook->attributes->url, 'pix') !== false;
                    if($isPix && $isUrlPix) {
                        return true;
                    }
                    //retorna as url que não contenha o nome pix
                    if(!$isPix && !$isUrlPix) {
                        return true;
                    }

                    
                }
            });

            $webhooks = array_map(function ($webhook) {
                return $webhook->attributes;
            }, $webhooks);
        }

        return $webhooks;
    }

    /**
     * Trata o postback retornado pelo gateway
     */
    public function billetVerify($request, $transaction_id = null)
    {
        try {
            if (!isset($request->attributes->boleto)) {
                return [
                    'success'       =>  false,
                    'status'        =>  '',
                    'transaction_id'=>  ''
                ];
            }
            if ($transaction_id) {
                $transaction    =   Transaction::find($transaction_id);
                $retrieve       =   $this->retrieve($transaction);
                return [
                    'success'           =>  true,
                    'status'            =>  $retrieve['status'],
                    'transaction_id'    =>  $retrieve['transaction_id']
                ];
            } else {
                $postbackTransaction = $request->id;

                if (!$postbackTransaction) {
                    return [
                        'success'       =>  false,
                        'status'        =>  '',
                        'transaction_id'=>  ''
                    ];
                }

                $transaction    =   Transaction::getTransactionByGatewayId($postbackTransaction);
                $retrieve       =   $this->retrieve($transaction);

                return [
                    'success'           =>  true,
                    'status'            =>  $retrieve['status'],
                    'transaction_id'    =>  $retrieve['transaction_id']
                ];
            }
        } catch (Exception $ex) {
            \Log::error($ex->getMessage());

            return [
                'success'       =>  false,
                'status'        =>  '',
                'transaction_id'=>  ''
            ];
        }
    }

    /**
     * Capture the payment of an existing, uncaptured, charge with split rules
     *
     * @param Transaction   $transaction
     * @param Provider      $provider
     * @param Decimal       $totalAmount        A positive decimal representing how much to charge
     * @param Decimal       $providerAmount
     * @param Payment       $payment
     *
     * @return Array ['success', 'status', 'captured', 'paid', 'transaction_id']
     */
    public function captureWithSplit(Transaction $transaction, Provider $provider, $totalAmount, $providerAmount, Payment $payment = null)
    {
        try {
            $retrievedCharge =  $this->retrieve($transaction);

            $this->checkException($retrievedCharge, 'api_retrievecapture_split_error');

            $newAmount       =  null;

            if ($retrievedCharge['amount'] != $totalAmount) {
                $newAmount   =  $totalAmount;
            }

            if ($newAmount > $retrievedCharge['amount']) {
                $amountParam =  null;
            } else {
                $amountParam =  $newAmount;
            }

            $response = IpagApi::captureWithSplit($transaction, $provider, $providerAmount, $amountParam);

            if (
                isset($response->success) &&
                $response->success &&
                isset($response->data) &&
                (
                    $response->data->attributes->status->message == 'CAPTURED' ||
                    $response->data->attributes->status->message == 'PRE-AUTHORIZED'
                )
            ) {
                $statusMessage = $response->data->attributes->status->message;
                $result = array(
                    'success' 		 => true,
                    'captured' 		 => $statusMessage == 'CAPTURED' ? true : false,
                    'paid' 			 => $statusMessage == 'CAPTURED' ? true : false,
                    'status' 		 => $statusMessage == 'CAPTURED' ? 'paid' : 'authorized',
                    'transaction_id' => (string)$response->data->id
                );
                return $result;
            } else {
                return array(
                    "success" 	=> false ,
                    'data' 		=> null,
                    'error' 	=> array(
                        "code" 		=> ApiErrors::CARD_ERROR,
                        "messages" 	=> array(trans('creditCard.customerCreationFail'))
                    )
                );
            }
        } catch (\Throwable $th) {
            \Log::error($th->__toString());

            return array(
                "success" 	=> false ,
                'data' 		=> null,
                'error' 	=> array(
                    "code" 		=> ApiErrors::CARD_ERROR,
                    "messages" 	=> array(trans('creditCard.customerCreationFail'))
                )
            );
        }
    }

    /**
     * Capture the payment of an existing, uncaptured, charge
     *
     * @param Transaction   $transaction
     * @param Decimal       $totalAmount        A positive decimal representing how much to capture
     * @param Payment       $payment
     *
     * @return Array ['success', 'status', 'captured', 'paid', 'transaction_id']
     */
    public function capture(Transaction $transaction, $amount, Payment $payment = null)
    {
        try {
            $response = IpagApi::capture($transaction, $amount);
            $response = HandleResponseIpag::handle($response);


            if (!$response['success']) {
                return $response;
            }

            $response = $response['data'];

            $isAttributes = isset($response->attributes);
            $isCaptured = $isAttributes && $response->attributes->status->message == 'CAPTURED';

            if ($isCaptured) {
                return array(
                    'success' 		 => true,
                    'captured' 		 => $isCaptured ? true : false,
                    'paid' 			 => $isCaptured ? true : false,
                    'status' 		 => $isCaptured ? 'paid' : '',
                    'transaction_id' => (string)$response->id
                );
            } else {
                return array(
                    "success" 	=> false ,
                    'data' 		=> null,
                    'error' 	=> array(
                        "code" 		=> ApiErrors::CARD_ERROR,
                        "messages" 	=> array(trans('creditCard.customerCreationFail'))
                    )
                );
            }
        } catch (\Throwable $th) {
            \Log::error($th->__toString());

            return array(
                "success" 	=> false ,
                'data' 		=> null,
                'error' 	=> array(
                    "code" 		=> ApiErrors::CARD_ERROR,
                    "messages" 	=> array(trans('creditCard.customerCreationFail'))
                )
            );
        }
    }

    /**
     * Refund a charge that has previously been created with split rules
     *
     * @param Transaction   $transaction
     * @param Payment       $payment
     *
     * @return Array ['success', 'status', 'transaction_id']
     */
    public function refundWithSplit(Transaction $transaction, Payment $payment)
    {
        try {
            return $this->refund($transaction, $payment);
        } catch (\Throwable $ex) {
            Log::error($ex->__toString());

            return array(
                "success" 			=> false ,
                "type" 				=> 'api_refund_error' ,
                "code" 				=> 'api_refund_error',
                "message" 			=> $ex->getMessage(),
                "transaction_id" 	=> ''
            );
        }
    }

    /**
     * Refund a charge that has previously been created
     *
     * @param Transaction   $transaction
     * @param Payment       $payment
     *
     * @return Array ['success', 'status', 'transaction_id']
     */
    public function refund(Transaction $transaction, Payment $payment)
    {
        try {
            $response = IpagApi::refund($transaction);

            if (
                isset($response->success) &&
                $response->success &&
                isset($response->data) &&
                $response->data->attributes->status->message == 'CANCELED'
            ) {
                $result = array(
                    "success"           =>  true ,
                    "status"            =>  'refunded',
                    "transaction_id"    =>  (string)$response->data->id
                );

                return $result;
            } elseif (
                isset($response->success) &&
                !$response->success &&
                isset($response->message)
            ) {
                \Log::info('Error refund IPag: ' . json_encode($response));

                $message =  $response->message;
                if (gettype($message) == 'string') {
                    try {
                        $message = json_decode($message);
                    } catch (\Throwable $th) {
                        //throw $th;
                    }

                    $code = 'api_refund_error';
                    if (isset($message->error)) {
                        $code = $message->error->code;
                        $message = $message->error->message;
                    }
                }

                return array(
                    "success" 			=> false ,
                    "type" 				=> 'api_refund_error' ,
                    "code" 				=> $code,
                    "message" 			=> $message,
                    "transaction_id" 	=> ''
                );
            }
        } catch (\Throwable $ex) {
            Log::error($ex->__toString());

            return array(
                "success" 			=> false ,
                "type" 				=> 'api_refund_error' ,
                "code" 				=> 'api_refund_error',
                "message" 			=> $ex->getMessage(),
                "transaction_id" 	=> ''
            );
        }
    }

    /**
     * Retrieves the details of a charge that has previously been created
     *
     * @param Transaction   $transaction
     *
     * @return Array ['success', 'transaction_id', 'amount', 'destination', 'status', 'card_last_digits']
     */
    public function retrieve(Transaction $transaction, Payment $payment = null)
    {
        try {
            $response = IpagApi::retrieve($transaction);

            if (
                isset($response->success) &&
                $response->success &&
                isset($response->data) &&
                isset($response->data->attributes->status->code)
            ) {
                return array(
                    'success' 			=> true,
                    'transaction_id' 	=> (string)$response->data->id,
                    'amount' 			=> $response->data->attributes->amount,
                    'destination' 		=> '',
                    'status' 			=> $this->getStatusString($response->data->attributes->status->code),
                    'card_last_digits' 	=> $payment ? $payment->last_four : ''
                );
            } else {
                \Log::error($response->message);

                return array(
                    "success" 			=> false ,
                    "type" 				=> 'api_retrieve_error' ,
                    "code" 				=> 'api_retrieve_error',
                    "message" 			=> $response->message
                );
            }
        } catch (\Throwable $th) {
            \Log::error($th->__toString());

            return array(
                "success" 			=>  false ,
                "type" 				=>  'api_refund_error' ,
                "code" 				=>  'api_refund_error',
                "message" 			=>  $th->getMessage(),
                "transaction_id" 	=>  ''
            );
        }
    }

    /**
     *  Create a new credit card
     *
     * @param Payment       $payment
     * @param User          $user               The customer that this card belongs to
     *
     * @return Array ['success', 'token', 'card_token', 'customer_id', 'card_type', 'last_four']
     */
    public function createCard(Payment $payment, User $user = null)
    {
        $cardNumber 			= $payment->getCardNumber();
        $cardExpirationMonth 	= $payment->getCardExpirationMonth();
        $cardExpirationYear 	= $payment->getCardExpirationYear();
        $cardCvc 				= $payment->getCardCvc();
        $cardHolder 			= $payment->getCardHolder();
        $userName				= $user->first_name." ".$user->last_name;
        $userDocument				= str_replace(".", "", $user->document);

        // $cpf = $this->cleanCpf($user->document);

        $result = array(
            'success'		=>	true,
            'customer_id'	=>	'',
            'last_four'		=>	substr($cardNumber, -4),
            'card_type'		=>	detectCardType($cardNumber),
            'card_token'	=>	'',
            'token'	        =>	'',
            'gateway'       => 'ipag'
        );

        return $result;
    }

    /**
     *  Delete a existing credit card
     *
     * @param Payment       $payment
     * @param User          $user               The customer that this card belongs to
     *
     * @return Array ['success']
     */
    public function deleteCard(Payment $payment, User $user = null)
    {
        $result = array(
            'success'	=>	true
        );
        return $result;
    }


    /**
     *  Create accounts for users
     *
     * @param LedgerBankAccount       $ledgerBankAccount
     *
     * @return Array ['success', 'recipient_id']
     */
    public function createOrUpdateAccount(LedgerBankAccount $ledgerBankAccount)
    {
        try {
            $newAccount = IpagApi::createOrUpdateAccount($ledgerBankAccount);
            $newAccount = HandleResponseIpag::handle($newAccount);
            if (strpos($newAccount['original_message'], 'already exists') !== false) {
                $newAccount = IpagApi::getSellerByLedgerBankAccount($ledgerBankAccount);
                $newAccount = HandleResponseIpag::handle($newAccount, );
            }

            if (!$newAccount['success']) {
                return $newAccount;
            }

            $newAccount = $newAccount['data'];
            if (isset($newAccount->data) && !empty($newAccount->data)) {
                $newAccount = $newAccount->data;

                if (gettype($newAccount) == 'array') {
                    $newAccount = $newAccount[0];
                }
            }


            if (isset($newAccount->id)) {
                $ledgerBankAccount->recipient_id = $newAccount->id;
                $ledgerBankAccount->save();
                return array(
                    'success'       =>  true,
                    'recipient_id'  =>  $ledgerBankAccount->recipient_id
                );
            }
        } catch (\Throwable $ex) {
            \Log::error($ex->__toString());

            $result = array(
                "success"               =>  false ,
                "recipient_id"          =>  'empty',
                "type"                  =>  'api_bankaccount_error' ,
                "code"                  =>  500 ,
                "message"               =>  trans("empty.".$ex->getMessage())
            );

            return $result;
        }
    }

    /**
     *  Return a gateway fee
     *
     * @return Decimal
     */
    public function getGatewayFee()
    {
        return 0.5;
    }

    /**
     *  Return a gateway tax
     *
     * @return Decimal
     */
    public function getGatewayTax()
    {
        return 0.5;
    }

    /**
     *  Return a date for the next compensation
     *
     * @return Carbon
     */
    public function getNextCompensationDate()
    {
        $carbon = Carbon::now();
        $compDays = Settings::findByKey('compensate_provider_days');
        $addDays = ($compDays || (string)$compDays == '0') ? (int)$compDays : 31;
        $carbon->addDays($addDays);

        return $carbon;
    }

    /**
     *  Return a bool value that determine if auto transfer to provider is enabled
     *
     * @return bool
     */
    public function checkAutoTransferProvider()
    {
        try {
            if (Settings::findByKey('auto_transfer_provider_payment') == "1") {
                return(true);
            } else {
                return(false);
            }
        } catch(Exception $ex) {
            \Log::error($ex);

            return(false);
        }
    }

    //finish
    public function debit(Payment $payment, $amount, $description)
    {
        \Log::error('debit_not_implemented');

        return array(
            "success" 			=> false,
            "type" 				=> 'api_debit_error',
            "code" 				=> 'api_debit_error',
            "message" 			=> 'debit_not_implemented',
            "transaction_id" 	=> ''
        );
    }

    //finish
    public function debitWithSplit(Payment $payment, Provider $provider, $totalAmount, $providerAmount, $description)
    {
        \Log::error('debit_split_not_implemented');

        return array(
            "success" 			=> false,
            "type" 				=> 'api_debit_error',
            "code" 				=> 'api_debit_error',
            "message" 			=> 'split_not_implementd',
            "transaction_id" 	=> ''
        );
    }

    /**
     * Returns status string based on status code
     *
     * @param Integer        $statusCode    Payment status code captured on gateway.
     *
     * @return String                       String related to the payment status code.
     *
     */
    private function getStatusString($statusCode)
    {
        switch ($statusCode) {
            case self::CODE_CREATED:
            case self::CODE_IN_ANALISYS:
            case self::CODE_PARTIAL_CAPTURED:
            case self::CODE_IN_DISPUTE:
                return self::PAYMENT_NOTFINISHED;
            case self::CODE_PRE_AUTHORIZED:
                return self::PAYMENT_AUTHORIZED;
            case self::CODE_CAPTURED:
                return self::PAYMENT_PAID;
            case self::CODE_DECLINED:
                return self::PAYMENT_DENIED;
            case self::CODE_CANCELED:
                return self::PAYMENT_VOIDED;
            case self::CODE_CHARGEDBACK:
                return self::PAYMENT_REFUNDED;
            case self::CODE_WAITING_PAYMENT:
                return self::PAYMENT_PENDING;
            default:
                return 'not_geted';
        }
    }

    public function pixCharge($amount, $user)
    {
        try {
            $response = IpagApi::pixCharge($amount, $user);

            if (
                isset($response->success) &&
                $response->success &&
                isset($response->data->attributes) &&
                isset($response->data->attributes->status) &&
                ($response->data->attributes->status->code == self::CODE_CREATED ||
                $response->data->attributes->status->code == self::CODE_WAITING_PAYMENT)
            ) {
                //add minutesin settings
                $minutes = Settings::getExpirationTimePix();
                $expirationDate = strtotime($response->data->attributes->created_at);
                $expirationDate = date('Y-m-d H:i:s', $expirationDate + $minutes * 60);

                return array(
                    'success'                   =>  true,
                    'captured'                  =>  false,
                    'paid'                      =>  false,
                    'status'                    =>  self::WAITING_PAYMENT,
                    'transaction_id'            =>  (string)$response->data->id,
                    'qr_code_base64'            =>  $response->data->attributes->pix->qrcode,
                    'copy_and_paste'            =>  $response->data->attributes->pix->qrcode,
                    'expiration_date_time'      =>  $expirationDate,
                    'billet_expiration_date'    =>  $expirationDate
                );
            } elseif (
                isset($response->success) &&
                $response->success &&
                isset($response->data->attributes) &&
                isset($response->data->attributes->status) &&
                ($response->data->attributes->status->code != self::CODE_WAITING_PAYMENT ||
                $response->data->attributes->status->code != self::CODE_WAITING_PAYMENT)
            ) {
                $message = '';
                $code = $response->data->attributes->status->code;

                $acquirer = $response->data->attributes->acquirer;
                $status = $response->data->attributes->status;
                if ($status) {
                    $code = $status->code;
                    $message = $status->message;
                }

                if (isset($acquirer->message)) {
                    $message = $acquirer->message;
                }

                \Log::info('pixCharge > Error 1:' . json_encode($response));

                return array(
                    "success" 				=>  false,
                    "type" 					=>  'api_charge_error',
                    "code" 					=>  $code,
                    "message" 				=>  $message,
                    "transaction_id"		=>  '',
                    'billet_expiration_date'=>  ''
                );
            } elseif (isset($response->success)  && !$response->success) {
                $message = '';
                $code = '';
                if (isset($response->message)) {
                    if (gettype($response->message) == 'string') {
                        $message = json_decode($response->message);
                        if (gettype($message) == 'object' && isset($message->error)) {
                            $code = $message->error->code;
                            $message = $message->error->message;
                        } else {
                            $message = $response->message;
                        }
                    }
                } elseif (isset($response->data)) {
                    $acquirer = $response->data->attributes->acquirer;
                    if ($acquirer) {
                        $code = $acquirer->code;
                        $message = $acquirer->message;
                    } else {
                        $status = $response->data->attributes->status;
                        if ($status) {
                            $code = $status->code;
                            $message = $status->message;
                        }
                    }
                }

                \Log::error('pixCharge > Error 2' . json_encode($response));

                return array(
                    "success" 				=>  false,
                    "type" 					=>  'api_charge_error',
                    "code" 					=>  $code,
                    "message" 				=>  $message,
                    "transaction_id"		=>  '',
                    'billet_expiration_date'=>  ''
                );
            } else {
                return array(
                    "success" 				=>  false,
                    "type" 					=>  'api_charge_error',
                    "code" 					=>  '',
                    "message" 				=>  '',
                    "transaction_id"		=>  '',
                    'billet_expiration_date'=>  ''
                );
            }
        } catch (\Throwable $th) {
            \Log::error('pixCharge > Error Throwable: ' . $th->getMessage());


            return array(
                "success" 				=>  false,
                "type" 					=>  'api_charge_error',
                "code" 					=>  '',
                "message" 				=>  $th->getMessage(),
                "transaction_id"		=>  '',
                'billet_expiration_date'=>  '',
                'Throwable'             => json_encode($th)
            );
        }
    }

    public function retrievePix($transaction_id, $request = null)
    {
        \Log::error('retrieve_pix_not_implemented');
        return array(
            "success" 			=> false,
            'paid'				=> false,
            "value" 			=> '',
            "qr_code_base64"    => '',
            "copy_and_paste"    => ''
        );
    }

    // /**
    //  * Creates a new transaction when value updates
    //  *
    //  * @param Transaction   $transaction
    //  * @param String        $description        An arbitrary string which you can attach to describe a Charge object
    //  * @param Payment|null  $payment
    //  * @param Boolean       $split              When true, split transaction value on gateway.
    //  *
    //  * @return Array ['success', 'status', 'captured', 'paid', 'transaction_id']
    //  */
    // private function chargeToCapture(Transaction $transaction, $description, $payment, $split)
    // {
    //     try
    //     {
    //         $request = Requests::find($transaction->request_id);
    //         $payment = $payment ?? Payment::findDefaultOrFirstByUserId($request->user_id);
    //         $provider = Provider::find($request->confirmed_provider);

    //         if(
    //             $request &&
    //             isset($request->total) &&
    //             $request->total != $request->estimate_price &&
    //             $payment &&
    //             $provider
    //         )
    //         {
    //             $charge = $request->getCharge();
    //             $responseRefund = $this->refund($transaction, $payment);

    //             $this->checkException($responseRefund, 'api_refundrecharge_error');

    //             if($split)
    //                 $chargeResponse = $this->chargeWithSplit($payment, $provider, $request->total, $request->provider_commission, $description, false, null);
    //             else
    //                 $chargeResponse = $this->charge($payment, $request->total, $description, false, null);

    //             $this->checkException($chargeResponse, 'api_recharge_error');

    //             RequestCharging::createCardTransactionUpdateRequest($chargeResponse, $charge, $request, false);

    //             return $chargeResponse;
    //         }

    //         $this->checkException(["success" => false], 'api_recharge_params_error');

    //     } catch (\Throwable $th) {
    //         \Log::error($th->getMessage());

    // 		return array(
    // 			"success" 				=>  false,
    // 			"type" 					=>  'api_chargecapture_error',
    // 			"code" 					=>  'api_chargecapture_error',
    // 			"message" 				=>  trans('creditCard.try_charge_fail_message'),
    // 			"transaction_id"		=>  '',
    //             'billet_expiration_date'=>  ''
    // 		);
    //     }
    // }

    /**
     * In the absence of a positive response, creates an exception with error response
     *
     * @param Array         $response           Array corresponding a gateway responses
     * @param String        $errorMessage       An arbitrary string which you can attach to describe a fail
     *
     * @return void
     */
    private function checkException($response, $errorMessage)
    {
        if (!isset($response['success']) || !$response['success']) {
            throw new Exception($errorMessage);
        }
    }

    /**
     *
     * @param String        $Message       An arbitrary string to translate
     *
     * @return void
     */
    private function getTranslateMessage($message)
    {
        if (is_array($message)) {
            foreach ($message as $key => $value) {
                $message[$key] = trans($value);
            }
            return $message;
        } else {
            if (strpos($message, self::CUSTOMER_BLACK_LIST) !== false) {
                return trans('creditCard.transactionFailCustomerBlackList');
            }
            if (strpos($message, self::DECLINED) !== false) {
                return trans('creditCard.transactionFailDeclained');
            }
        }

        return trans('creditCard.customerCreationFail');
    }
}
