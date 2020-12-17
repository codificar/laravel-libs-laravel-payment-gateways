<?php

namespace Codificar\PaymentGateways\Libs;
use Carbon\Carbon;

use Getnet\API\Getnet;
use Getnet\API\Transaction as GetNetTransaction;
use Getnet\API\Token;
use Getnet\API\Credit;
use Getnet\API\Customer;
use Getnet\API\Card;
use Getnet\API\Order;
use Getnet\API\Boleto;
use Getnet\API\Request;

use Ramsey\Uuid\Uuid;

use DateTime, DateInterval;

//models do sistema
use Payment;
use Provider;
use Transaction;
use User;
use LedgerBankAccount;
use Settings;

class GetNetLib implements IPayment
{
    /**
     * Gateway status string
     */
    const GATEWAY_AUTHORIZED = 'AUTHORIZED';
    const GATEWAY_PENDING    = 'PENDING';
    const GATEWAY_PAID       = 'PAID';
    const GATEWAY_CONFIRMED  = 'CONFIRMED';
    const GATEWAY_APPROVED   = 'APPROVED';
    const GATEWAY_ACCEPTED   = 'ACCEPTED';
    const GATEWAY_CANCELED   = 'CANCELED';
    const GATEWAY_DENIED     = 'DENIED';
    const GATEWAY_ERROR      = 'ERROR';

    /**
     * Payment status string
     */
    const PAYMENT_AUTHORIZED = 'authorized';
    const PAYMENT_PENDING 	 = 'waiting_payment';
    const PAYMENT_CONFIRMED  = 'paid';
    const PAYMENT_REFUNDED   = 'refunded';
    const PAYMENT_REFUSED    = 'refused';

    /**
     * GetNet configs and objects
     */
    private $clientId;
    private $clientSecret;
    private $environment;
    private $sellerId;
    private $getnet;

    /**
     * Defined environment
     */
    public function __construct()
    {
        $this->environment    =   "SANDBOX";
        // $this->environment    =   "PRODUCTION";
    }

    /**
     * Defined config
     */
    private function setApiKey()
    {
        $this->clientId       =   Settings::findByKey('getnet_client_id');
        $this->clientSecret   =   Settings::findByKey('getnet_client_secret');
        $this->sellerId       =   Settings::findByKey('getnet_seller_id');

        if(!$this->clientId || !$this->clientSecret || !$this->sellerId)
            return $this->responseApiError("gateway_getnet.client_keys_fail");

        try {
            $this->getnet = new Getnet($this->clientId, $this->clientSecret, $this->environment);
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            return $this->responseApiError('gateway_getnet.sdk_fail');
        }
	}

    /**
     * Returns tokenized card on Gateway GetNet
     *
     * @param Object        $payment        Object that represents requester card.
     * @param Object        $user           Object that represents user on system.
     *
     * @return Array       [
     *                      'success',
     *                      'token',
     *                      'customer_id',
     *                      'last_four',
     *                      'card_type',
     *                      'card_token',
     *                      'gateway'
     *                     ]
     */
    public function createCard(Payment $payment, User $user = null)
    {
        $responseConf = $this->setApiKey();
        if(isset($responseConf['success']) && !$responseConf['success'])
            return $responseConf;

        $cardNumber = $payment->getCardNumber();

        try {

            $custumerId = Uuid::uuid4()->toString();

            $objToken  = new Token($cardNumber, $custumerId, $this->getnet);
            $cardToken = $objToken->getNumberToken();

            return array(
                'success'		=>	true,
                'token'         =>	$cardToken,
                'customer_id'	=>	"customer_" . $custumerId,
                'last_four'		=>	substr($cardNumber, -4),
                'card_type'		=>	detectCardType($cardNumber),
                'card_token'	=>	$cardToken,
                "gateway"       =>  "getnet"
            );

        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            return $this->responseApiError('gateway_getnet.new_card_fail');
        }
    }

    //finish
    public function deleteCard(Payment $payment, User $user = null)
    {
        return array(
			'success' => true
		);
    }

    /**
     * Returns authorized transaction information on Geteway GetNet
     *
     * @param Object        $payment        Object that represents requester card.
     * @param Integer       $amount         Integer that represents amount authorized on credit card.
     * @param String        $description    String that represents details of transaction.
     * @param Boolean       $capture        Boolean that represents config to capture on charge.
     * @param Object        $user           Object that represents user on system.
     *
     * @return Array       [
     *                      'success',
	 *			            'captured',
	 *			            'paid',
	 *			            'status',
	 *			            'transaction_id'
     *                     ]
     */
    public function charge(Payment $payment, $amount, $description, $capture = true, User $user = null)
    {
        $responseConf = $this->setApiKey();
        if(isset($responseConf['success']) && !$responseConf['success'])
            return $responseConf;

        $amount = floor($amount * 100);

        if($amount <= 0)
            return $this->responseApiError('gateway_getnet.amount_negative');

        $cardNumber             = $payment->getCardNumber();
        $cardExpirationMonth 	= $payment->getCardExpirationMonth();
        $cardExpirationYear 	= $payment->getCardExpirationYear();
        $cardCvv 				= $payment->getCardCvc();
        $cardHolder 			= $payment->getCardHolder();
        $customerId             = $payment->customer_id;

        $cardExpirationYear = $cardExpirationYear % 100;

        try {
            // Paliativo pois precisa alterar todas as charges do sistema para ledger
            $client = $payment->user_id ? \User::find($payment->user_id) : \Provider::find($payment->provide_id);

            $transactionId = Uuid::uuid4()->toString();

            // Inicia uma transação
            $transaction = new GetNetTransaction();

            // Dados do pedido - Transação
            $transaction->setSellerId($this->sellerId);
            $transaction->setCurrency($this->getCurrency());
            $transaction->setAmount($amount);

            // Detalhes do Pedido
            $transaction->order($transactionId)
            ->setProductType("service")
            ->setSalesTax(0);

            $token = new Token($cardNumber, $customerId, $this->getnet);
            $payment->card_token = $token->number_token;
            $payment->save();
            // $token->number_token = $payment->card_token; // force system token

            // Dados do método de pagamento do comprador
            $transaction->credit("")
            ->setAuthenticated(false)
            ->setDelayed(true)
            ->setPreAuthorization(false)
            ->setNumberInstallments(1)
            ->setSaveCardData(false)
            ->setTransactionType("FULL")
            ->card($token)
                ->setBrand(strtolower($payment->card_type))
                ->setExpirationMonth(str_pad($cardExpirationMonth, 2, '0', STR_PAD_LEFT))
                ->setExpirationYear(str_pad($cardExpirationYear, 2, '0', STR_PAD_LEFT))
                ->setCardholderName($cardHolder)
                ->setSecurityCode($cardCvv);

            // Dados pessoais do comprador
            $transaction->customer($customerId)
            ->setDocumentType("CPF")
            ->setEmail($client->email)
            ->setFirstName($client->first_name)
            ->setLastName($client->last_name)
            ->setName($client->first_name . " " . $client->last_name)
            ->setPhoneNumber(preg_replace('/(\D)/', '', $client->phone))
            ->setDocumentNumber(preg_replace('/(\D)/', '', $client->document))
            ->billingAddress($client->zipcode)
                ->setCity($client->address_city)
                ->setComplement($client->address_complements ? $client->address_complements : "Sem complemento") //complemento eh obrigatorio. Caso nao tenha, coloca "sem complemento" para burlar o getnet
                ->setCountry($client->country)
                ->setDistrict($client->address_neighbour)
                ->setNumber((String)$client->address_number)
                ->setPostalCode(trim(str_replace("-","",$client->zipcode)))
                ->setState($client->state)
                ->setStreet($client->address);

            $charge       = $this->getnet->authorize($transaction);
            $chargeStatus = $charge->getStatus();
            $paymentId    = $charge->getPaymentId();

            //Se foi authorizado, mas na verdade ja era pra ser capturado, entao realiza a captura de uma vez.
            if($chargeStatus == self::GATEWAY_AUTHORIZED && $capture == true){
                $charge = $this->getnet->authorizeConfirm($paymentId);
            }

            //Se o status nao foi capturado e nem autorizado, entao houve um erro 
            if($charge->getStatus() != self::GATEWAY_CONFIRMED && $charge->getStatus() != self::GATEWAY_AUTHORIZED) {
                return $this->responseApiError('paymentError.refused');
            }             
            else {
                return array (
                    'success'        => true,
                    'captured'       => $charge->getStatus() == self::GATEWAY_CONFIRMED ? true : false,
                    'paid'           => $charge->getStatus() == self::GATEWAY_CONFIRMED ? true : false,
                    'status'      => $charge->getStatus() == self::GATEWAY_CONFIRMED ? 'paid' : 'authorized',
                    'transaction_id' => $paymentId
                );
            }

        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            return $this->responseApiError('gateway_getnet.charge_fail');
        }
    }

    /**
     * Returns confirmed transaction information on Geteway GetNet
     *
     * @param Object        $transaction    Object that represents system transaction.
     * @param Integer       $amount         Integer that represents amount to caṕture on credit card.
     * @param Object        $payment        Object that represents requester card.
     *
     * @return Array       [
     *                      'success',
	 *			            'status',
	 *			            'captured',
	 *			            'paid',
	 *			            'transaction_id'
     *                     ]
     */
    public function capture(Transaction $transaction, $amount, Payment $payment = null)
	{
        $responseConf = $this->setApiKey();
        if(isset($responseConf['success']) && !$responseConf['success'])
            return $responseConf;

        $amount = floor($amount * 100);

        if($amount <= 0)
            return $this->responseApiError('gateway_getnet.amount_negative');

        try {

            $capture        =   $this->getnet->authorizeConfirm($transaction->gateway_transaction_id);
            $captureStatus  =   $capture->getStatus();

            if($captureStatus != self::GATEWAY_CONFIRMED)
			{
                return $this->responseApiError('paymentError.refused');
            }

            return array(
                'success'        => true,
				'status'         => self::PAYMENT_CONFIRMED,
				'captured'       => true,
				'paid'           => true,
				'transaction_id' => $transaction->gateway_transaction_id
            );

        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            return $this->responseApiError('gateway_getnet.capture_fail');
        }
    }

    /**
     * Returns transaction information on Geteway GetNet
     *
     * @param Object        $transaction    Object that represents system transaction.
     * @param Object        $payment        Object that represents requester card.
     *
     * @return Array       [
     *                      'success',
     *                      'transaction_id',
     *                      'amount',
     *                      'destination',
     *                      'status',
     *                      'card_last_digits'
     *                     ]
     */
    public function retrieve(Transaction $transaction, Payment $payment = null)
    {
        $responseConf = $this->setApiKey();
        if(isset($responseConf['success']) && !$responseConf['success'])
            return $responseConf;

        return array(
            'success'           => true,
            'transaction_id'    => $transaction->gateway_transaction_id,
            'amount'            => $transaction->gross_value,
            'destination'       => '',
            'status'            => $transaction->status,
            'card_last_digits'  => $payment->last_four
        );
    }

    /**
     * Cancel and returns transaction information on Geteway GetNet
     *
     * @param Object        $transaction    Object that represents system transaction.
     * @param Object        $payment        Object that represents requester card.
     *
     * @return Array       [
     *                      'success',
     *                      'status',
     *                      'transaction_id'
     *                     ]
     */
    public function refund(Transaction $transaction, Payment $payment)
    {
        $responseConf = $this->setApiKey();
        if(isset($responseConf['success']) && !$responseConf['success'])
            return $responseConf;

        try {
            $createDateTime = new DateTime($transaction->created_at);
            $nowDateTime    = new DateTime(date(now()));

            $interval   = date_diff($createDateTime, $nowDateTime);

            //cancelTransaction precisa criar uma cancelKey para cada pedido de cancelamento
            $cancelKey  = Uuid::uuid4()->toString();
            $cancelKey  = str_replace('-','',$cancelKey);

            //cancelTransaction é utilizado para transações com mais de 1 dia
            //$transaction->gross_value precisa ser menor ou igual ao valor original
            if($interval->days > 0)
                $refund = $this->getnet->cancelTransaction($transaction->gateway_transaction_id, $transaction->gross_value, $cancelKey);
            else
                $refund = $this->getnet->authorizeCancel($transaction->gateway_transaction_id, $transaction->gross_value);

            $refundStatus   =   $refund->getStatus();

            if($refundStatus != self::GATEWAY_ACCEPTED && $refundStatus != self::GATEWAY_CANCELED)
                return $this->responseApiError('gateway_getnet.refund_fail');

            return array(
                "success" 					=> true ,
                "status" 					=> self::PAYMENT_REFUNDED,
                "transaction_id"			=> $transaction->gateway_transaction_id                    
            );

        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            return $this->responseApiError('gateway_getnet.refund_fail');
        }
    }

    /**
     * Creates a charge with billet on Geteway GetNet
     *
     * @param Integer       $amount                 Integer that represents amount to be charge on billet.
     * @param Object        $client                 Object that represents user or provider on system.
     * @param String        $postbackUrl            URL that the gateway will use to update billet status.
     * @param String        $billetExpirationDate   Expiration date of billet.
     * @param String        $billetInstructions     String that represents details of transaction.
     *
     * @return Array       [
     *                      'success',
     *                      'captured',
     *                      'paid',
     *                      'status',
     *                      'transaction_id',
     *                      'billet_url',
     *                      'billet_expiration_date'
     *                     ]
     */
    public function billetCharge($amount, $client, $postbackUrl, $billetExpirationDate, $billetInstructions = "")
    {
        $responseConf = $this->setApiKey();
        if(isset($responseConf['success']) && !$responseConf['success'])
            return $responseConf;

        $amount = floor($amount * 100);

        try {
            //A data de vencimento nao pode ser hoje. Precisa ser de pelo menos 1 dia.
            if($billetExpirationDate == date("Y-m-d")) {
                $billetDateAux = new DateTime($billetExpirationDate);
                $billetExpirationDate = $billetDateAux->add(new DateInterval('P1D'))->format('d/m/Y'); //adiciona 1 dia do vencimento
            } else {
                $billetDateAux = new DateTime($billetExpirationDate);
                $billetExpirationDate = $billetDateAux->format('d/m/Y');
            }
            $transactionId = Uuid::uuid4()->toString();

            list($microSeconds, $seconds) = explode(" ", microtime());
            $documentNumber = $seconds.substr($microSeconds,2,-3);

            $custumerId = Uuid::uuid4()->toString();

            //Cria a transação
            $transaction = new GetNetTransaction();
            $transaction->setSellerId($this->sellerId);
            $transaction->setCurrency($this->getCurrency());
            $transaction->setAmount($amount);

            //Adicionar dados do Pedido
            $transaction->order($transactionId)
                ->setProductType("service")
                ->setSalesTax(0);

            $transaction->boleto('')
                ->setDocumentNumber($documentNumber)
                ->setExpirationDate($billetExpirationDate)
                ->setProvider("santander")
                ->setInstructions($billetInstructions);

            //Adicionar dados do cliente
            $transaction->customer($custumerId)
                ->setDocumentType("CPF")
                ->setFirstName($client->first_name)
                ->setLastName($client->last_name)
                ->setName($client->first_name . " " . $client->last_name)
                ->setPhoneNumber(preg_replace('/(\D)/', '', $client->phone))
                ->setDocumentNumber(preg_replace('/(\D)/', '', $client->document))
                ->billingAddress($client->zipcode)
                    ->setCity($client->address_city)
                    ->setComplement($client->address_complements ? $client->address_complements : "Sem complemento") //complemento eh obrigatorio. Caso nao tenha, coloca "sem complemento" para burlar o getnet
                    ->setCountry($client->country)
                    ->setDistrict($client->address_neighbour)
                    ->setNumber((String)$client->address_number)
                    ->setPostalCode(trim(str_replace("-","",$client->zipcode)))
                    ->setState($client->state)
                    ->setStreet($client->address);

            $billet = $this->getnet->boleto($transaction);

            $billetStatus   =   $billet->getStatus();

            $paymentId      =   $billet->getPaymentId();

            $links    = json_decode($billet->getResponseJSON());

            $request = new Request($this->getnet);

            if($billetStatus != self::GATEWAY_PENDING)
                return $this->responseApiError('gateway_getnet.billet_fail');

            return array (
                'success' => true,
                'captured' => true,
                'paid' => true,
                'status' => self::GATEWAY_PENDING,
                'transaction_id' => $paymentId,
                'billet_url' => $request->getBaseUrl() . $links->boleto->_links[0]->href,
                'billet_expiration_date' => $billetExpirationDate
            );

        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            return $this->responseApiError('gateway_getnet.billet_fail');
        }
    }

    /**
     * Verifies billet status returned from postback by Gateway GetNet.
     *
     * @param Object        $request                 Object that represents postback returned by gateway.
     *
     * @return Array       [
     *                      'success',
     *                      'status',
     *                      'transaction_id',
     *                     ]
     */
    public function billetVerify($request)
    {
        $responseConf = $this->setApiKey();
        if(isset($responseConf['success']) && !$responseConf['success'])
            return $responseConf;

        try {
            $billetStatus   =   $request->status;
            $paymentId      =   $request->payment_id;

            return array (
                'success' => true,
                'status' => $this->getStatusString($billetStatus),
                'transaction_id' => $paymentId,
            );

        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            return $this->responseApiError('gateway_getnet.billet_verify_fail');
        }
    }

    //finish
    public function chargeWithSplit(Payment $payment, Provider $provider, $totalAmount, $providerAmount, $description, $capture = true, User $user = null)
    {
        \Log::error('chage_split_not_implemented');

        return array(
            "success" 			=> false ,
            "type" 				=> 'api_capture_error' ,
            "code" 				=> 'api_capture_error',
            "message" 			=> 'split_not_implementd',
            "transaction_id" 	=> ''
        );
    }

    //finish
    public function refundWithSplit(Transaction $transaction, Payment $payment)
    {
        \Log::error('refund_split_not_implemented');

        return array(
            "success" 			=> false ,
            "type" 				=> 'api_capture_error' ,
            "code" 				=> 'api_capture_error',
            "message" 			=> 'split_not_implementd',
            "transaction_id" 	=> $transaction->gateway_transaction_id
        );
    }

    //finish
    public function captureWithSplit(Transaction $transaction, Provider $provider, $totalAmount, $providerAmount, Payment $payment = null)
    {
        \Log::error('capture_split_not_implemented');

        return array(
            "success" 			=> false ,
            "type" 				=> 'api_capture_error' ,
            "code" 				=> 'api_capture_error',
            "message" 			=> 'split_not_implementd',
            "transaction_id" 	=> $transaction->gateway_transaction_id
        );
    }

    //finish
    public function createOrUpdateAccount(LedgerBankAccount $ledgerBankAccount)
    {
        return array(
			'success' => true,
			'recipient_id' => '',
		);
    }

    /**
     * Tax used on system transaction
     */
    public function getGatewayTax()
	{
		return 0.0399;
	}

    /**
     * Fee used on system transaction
     */
	public function getGatewayFee()
	{
		return 0.5 ;
    }
    
    /**
     * Boolean used on split config
     */
    public function checkAutoTransferProvider()
    {
        return false;
    }

    /**
     * Returns next compensation date
     */
    public function getNextCompensationDate()
    {
		$carbon = Carbon::now();
		$carbon->addDays(31);
		return $carbon ;
    }
    
    /**
     * Returns translated fails response on lib
     *
     * @param String        $message    String to translate.
     *
     * @return Array       [
     *                      'success',
     *                      'message'
     *                     ]
     */
    private function responseApiError($message)
    {
        \Log::error($message);

        return array(
            "success" 			=> false,
            "message" 			=> trans($message),
            "transaction_id"    => ''
        );
    }

    /**
     * Returns status string based on gateway status
     *
     * @param String        $status   Payment status captured on gateway.
     *
     * @return String                 String related to the payment status.
     *
     */
    private function getStatusString($status)
    {
        switch ($status) {
            case self::GATEWAY_AUTHORIZED:
                return self::PAYMENT_AUTHORIZED;
            case self::GATEWAY_PENDING:
                return self::PAYMENT_PENDING;
            case self::GATEWAY_CONFIRMED:
                return self::PAYMENT_CONFIRMED;
            case self::GATEWAY_ACCEPTED:
                return self::PAYMENT_REFUNDED;
            case self::GATEWAY_CANCELED:
                return self::PAYMENT_REFUSED;
            case self::GATEWAY_ERROR:
                return self::PAYMENT_REFUSED;
            case self::GATEWAY_PAID:
                return self::PAYMENT_CONFIRMED;
            case self::GATEWAY_APPROVED:
                return self::PAYMENT_CONFIRMED;
            case self::GATEWAY_DENIED:
                return self::PAYMENT_REFUSED;
            default:
                return 'not_geted';
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
     * Check if currency is 3 char length. If not, get the 'BRL' as default
     * Verifica se o currency tem 3 caracteres. Se nao tiver, entao utiliza o BRL como default.
     */
    private function getCurrency() {
        $keywords_currency = Settings::findByKey('generic_keywords_currency');
        
        if(strlen($keywords_currency) != 3) 
            return 'BRL';
        else 
            return $keywords_currency;
    }
}
