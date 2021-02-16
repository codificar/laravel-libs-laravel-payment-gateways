<?php

namespace Codificar\PaymentGateways\Libs;
use Carbon\Carbon;

use Cielo\API30\Merchant;
use Cielo\API30\Ecommerce\Environment;
use Cielo\API30\Ecommerce\Sale;
use Cielo\API30\Ecommerce\CieloEcommerce;
use Cielo\API30\Ecommerce\Payment as CieloPayment;
use Cielo\API30\Ecommerce\CreditCard;

use Cielo\API30\Ecommerce\Request\CieloRequestException;

use Ramsey\Uuid\Uuid;
use Exception;
//models do sistema
use Payment;
use Provider;
use Transaction;
use User;
use LedgerBankAccount;
use Settings;
use App;

class CieloLib implements IPayment
{
    /**
     * Payment status code
     */
    const CODE_NOTFINISHED  =   0;
    const CODE_AUTHORIZED   =   1;
    const CODE_CONFIRMED    =   2;
    const CODE_DENIED       =   3;
    const CODE_VOIDED       =   10;
    const CODE_REFUNDED     =   11;
    const CODE_PENDING      =   12;
    const CODE_ABORTED      =   13;
    const CODE_SCHEDULED    =   20;

    /**
     * Payment status string
     */
    const PAYMENT_NOTFINISHED  =   'not_finished';
    const PAYMENT_AUTHORIZED   =   'authorized';
    const PAYMENT_CONFIRMED    =   'paid';
    const PAYMENT_DENIED       =   'denied';
    const PAYMENT_VOIDED       =   'voided';
    const PAYMENT_REFUNDED     =   'refunded';
    const PAYMENT_PENDING      =   'pending';
    const PAYMENT_ABORTED      =   'aborted';
    const PAYMENT_SCHEDULED    =   'scheduled';
    
    /**
     * Cielo configs and objects
     */
    private $merchamtId;
    private $merchandtKey;
    private $environment;
    private $merchant;
    private $cieloEcommerce;


    /**
     * Defined environment
     */
    public function __construct()
    {
        // Configure o ambiente
        if (App::environment() == 'production') {
            $this->environment = Environment::production();
        } else {
            $this->environment = Environment::sandbox();
        }
    }

    /**
     * Defined config
     */
    private function setApiKey()
    {
        $this->merchantId     =   Settings::findByKey('cielo_merchant_id');
        $this->merchandtKey   =   Settings::findByKey('cielo_merchant_key');

        if(!$this->merchantId || !$this->merchandtKey)
            return $this->responseApiError("gateway_cielo.merchant_id_key_fail");

        // Configure seu merchant
        $this->merchant = new Merchant($this->merchantId, $this->merchandtKey);
        if(!$this->merchant)
            return $this->responseApiError("gateway_cielo.merchant_fail");

        // Configure o SDK com seu merchant e o ambiente apropriado
        $this->cieloEcommerce = new CieloEcommerce($this->merchant,$this->environment);
        if(!$this->cieloEcommerce)
            return $this->responseApiError("gateway_cielo.sdk_fail");
	}

    /**
     * Returns tokenized card on Gateway Cielo
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

        $cardNumber 			= $payment->getCardNumber();
        $cardExpirationMonth 	= $payment->getCardExpirationMonth();
        $cardExpirationYear 	= $payment->getCardExpirationYear();
        $cardCvv 				= $payment->getCardCvc();
        $cardHolder 			= $payment->getCardHolder();

        if($cardExpirationYear / 100 < 1)
            $cardExpirationYear += 2000;

        $cardType = strtolower(detectCardType($cardNumber)) == "mastercard" ? 'master' : strtolower(detectCardType($cardNumber));

        // Crie uma instância do objeto que irá retornar o token do cartão 
        $card = new CreditCard();
        $card->setCustomerName($cardHolder);
        $card->setCardNumber($cardNumber);
        $card->setHolder($cardHolder);
        $card->setExpirationDate(
            str_pad($cardExpirationMonth, 2, '0', STR_PAD_LEFT) . "/" . $cardExpirationYear
        );
        $card->setBrand($cardType);

        try {
            // Configure o SDK com seu merchant e o ambiente apropriado para recuperar o cartão
            $tokenizeCard = $this->cieloEcommerce->tokenizeCard($card);

            // Get the token
            $cardToken = $tokenizeCard->getCardToken();

            return array(
                'success'		=>	true,
                'token'         =>	$cardToken,
                'customer_id'	=>	$cardToken,
                'last_four'		=>	substr($cardNumber, -4),
                'card_type'		=>	$cardType,
                'card_token'	=>	$cardToken,
                "gateway"       =>  "cielo"
            );

        } 
        //Capture erros on cielo. If is a internal general error, capture after
        catch (CieloRequestException $e) {
            $error = $e->getCieloError();
            if(!$error) { $error = $e; }
            \Log::error($error->getMessage()); //log cielo error
            return $this->responseApiError('gateway_cielo.new_card_fail');
        } 
        //Capture general error
        catch(Exception $e){
            $error = $e->getMessage();
            return $this->responseApiError('gateway_cielo.new_card_fail');
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
     * Returns authorized transaction information on Geteway Cielo
     *
     * @param Object        $payment        Object that represents requester card.
     * @param Decimal       $amount         Decimal that represents amount authorized on credit card.
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

        $captureStatus = self::CODE_NOTFINISHED;
        $amount = floor($amount * 100);

        if($amount <= 0)
            return $this->responseApiError('gateway_cielo.amount_negative');

        $cardNumber = $payment->getCardNumber();
        $cardCvv    = $payment->getCardCvc();

        // Crie uma instância de Sale
        $sale = new Sale(Uuid::uuid4()->toString());

        // Crie uma instância de Payment informando o valor do pagamento
        $cieloPayment = $sale->payment($amount);

        $cardType = strtolower($payment->card_type) == "mastercard" ? 'master' : $payment->card_type;

        // Crie uma instância de Credit Card
        $cieloPayment->setType(CieloPayment::PAYMENTTYPE_CREDITCARD)
            ->creditCard($cardCvv, $cardType)
            ->setCardToken($payment->card_token);

        // Crie o pagamento na Cielo
        try {
            // Configure o SDK com seu merchant e o ambiente apropriado para criar a venda
            $sale = $this->cieloEcommerce->createSale($sale);

            // Com a venda criada na Cielo, já temos o ID do pagamento, TID e demais
            // dados retornados pela Cielo
            $paymentId      =   $sale->getPayment()->getPaymentId();
            $chargeStatus   =   $sale->getPayment()->getStatus();

            // Com o ID do pagamento, podemos fazer sua captura, se ela não tiver sido capturada ainda
            if($chargeStatus == self::CODE_AUTHORIZED && $capture == true){
                $sale           =   $this->cieloEcommerce->captureSale($paymentId, $amount, 0);
                $captureStatus  =   $sale->getStatus();
            }

            if($chargeStatus != self::CODE_AUTHORIZED || ($captureStatus != self::CODE_CONFIRMED  && $capture == true))
			{
                return $this->responseApiError('paymentError.refused');
			}

			return array (
				'success'        => true,
				'captured'       => $capture,
				'paid'           => $capture,
				'status'         => $capture ? self::PAYMENT_CONFIRMED : self::PAYMENT_AUTHORIZED,
				'transaction_id' => $paymentId
            );

        } catch (CieloRequestException $e) {
            $error = $e->getCieloError();
            if(!$error)
                $error = $e;
            \Log::error($error->getMessage());
            return $this->responseApiError('gateway_cielo.charge_fail');
        }
    }

    /**
	 * Função para gerar boletos de pagamentos
	 * @param int $amount valor do boleto
	 * @param User/Provider $client instância do usuário ou prestador
	 * @param string $postbackUrl url para receber notificações do status do pagamento
	 * @param string $boletoExpirationDate data de expiração do boleto
	 * @param string $boletoInstructions descrição no boleto
	 * @return array       [
     *                      'success',
	 *			            'captured',
	 *			            'paid',
	 *			            'status',
     *			            'transaction_id',
     *                      'billet_url',
     *                      'billet_expiration_date'
     *                     ]
     */
    public function billetCharge ($amount, $client, $postbackUrl = null, $billetExpirationDate, $billetInstructions = '')
    {
        $responseConf = $this->setApiKey();
        if(isset($responseConf['success']) && !$responseConf['success'])
            return $responseConf;
        
        $amount = floor($amount * 100);

        // Crie uma instância de Sale
        $sale = new Sale(Uuid::uuid4());

        // Crie uma instância de Customer informando o nome do cliente,
        // documento e seu endereço
        $customer = $sale->customer($client->getFullName())
        ->setIdentity($client->getDocument())
        ->address()->setZipCode($client->zipcode)
                ->setCountry('BRA')
                ->setState($this->convertStateString($client->state))
                ->setCity($client->address_city)
                ->setDistrict($client->address_neighbour)
                ->setStreet($client->address)
                ->setNumber($client->address_number);

        // Crie uma instância de Payment informando o valor do pagamento
        $cieloPayment = $sale->payment($amount);
        
        $cieloPayment->setType(CieloPayment::PAYMENTTYPE_BOLETO)
            ->setAssignor(Settings::findByKey('website_title'))
            ->setExpirationDate(date('Y-m-d', strtotime($billetExpirationDate)))
            ->setInstructions($billetInstructions);

        // Crie o pagamento na Cielo
        try {
            // Configure o SDK com seu merchant e o ambiente apropriado para criar a venda
            $sale = $this->cieloEcommerce->createSale($sale);

            // Com a venda criada na Cielo, já temos o ID do pagamento, TID e demais
            // dados retornados pela Cielo
            $paymentId      = $sale->getPayment()->getPaymentId();
            $chargeStatus   = $sale->getPayment()->getStatus();
            $billetURL      = $sale->getPayment()->getUrl();
            $expiration     = $sale->getPayment()->getExpirationDate();

            return array (
				'success' => true,
				'captured' => true,
				'paid' => ($this->getStatusString($chargeStatus) == self::PAYMENT_CONFIRMED),
				'status' => $this->getStatusString($chargeStatus),
				'transaction_id' => $paymentId,
				'billet_url' => $billetURL,
				'billet_expiration_date' => $expiration
			);

        } catch (CieloRequestException $e) {
            $error = $e->getCieloError();
            
            if(!$error)
                $error = $e;
            \Log::error($error->getMessage());
            return $this->responseApiError('gateway_cielo.charge_fail');
        }
    }

    /**
	 * Trata o postback retornado pelo gateway
     * @param object $request
     * @return array [
     *                  'success',
     *                  'status',
     *                  'transaction_id',
     *               ]
	 */
	public function billetVerify ($request, $transaction_id = null)
	{
		$postbackTransaction = $request->PaymentId;
        
		if (!$postbackTransaction)
			return [
				'success' => false,
				'status' => '',
				'transaction_id' => ''
            ];
        
        $transaction = Transaction::getTransactionByGatewayId($postbackTransaction);
        $retrieve = $this->retrieve($transaction);

		return [
			'success' => true,
			'status' => $retrieve['status'],
			'transaction_id' => $retrieve['transaction_id']
		];
	}

    /**
     * Returns confirmed transaction information on Geteway Cielo
     *
     * @param Object        $transaction    Object that represents system transaction.
     * @param Decimal       $amount         Decimal that represents amount to caṕture on credit card.
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
            return $this->responseApiError('gateway_cielo.amount_negative');

        try {

            $responseRetrieve = $this->retrieve($transaction, $payment);

            if(!$responseRetrieve['success'] || ($responseRetrieve['status'] != self::PAYMENT_NOTFINISHED && $responseRetrieve['status'] != self::PAYMENT_AUTHORIZED))
                return $this->responseApiError('gateway_cielo.capture_retrieve_fail');

            if(strlen($responseRetrieve['transaction_id']) != 36)
                return $this->responseApiError('gateway_cielo.paymentid_lenght_fail');

            $sale           =   $this->cieloEcommerce->captureSale($responseRetrieve['transaction_id'], $amount, 0);
            $captureStatus  =   $sale->getStatus();

            if($captureStatus != self::CODE_CONFIRMED)
			{
                return $this->responseApiError('paymentError.refused');
            }

            return array(
                'success'        => true,
				'status'         => self::PAYMENT_CONFIRMED,
				'captured'       => true,
				'paid'           => true,
				'transaction_id' => $responseRetrieve['transaction_id']
            );

        } catch (CieloRequestException $e) {
            $error = $e->getCieloError();
            if(!$error)
                $error = $e;
            \Log::error($error->getMessage());
            return $this->responseApiError('gateway_cielo.capture_fail');
        }
    }

    /**
     * Returns transaction information on Geteway Cielo
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

        try {
            // Recupera a venda
            $sale       =   $this->cieloEcommerce->getSale($transaction->gateway_transaction_id);

            if(!is_object($sale)){
                \Log::error(print_r($sale,1));
                return $this->responseApiError('gateway_cielo.retrieve_fail');
            }

            $paymentId  =   $sale->getPayment()->getPaymentId();
            $amount     =   $sale->getPayment()->getAmount();
            $status     =   $sale->getPayment()->getStatus();
            $cardNumber =   $payment ? $payment->last_four : '';            

            return array(
                'success'           => true,
                'transaction_id'    => $paymentId,
                'amount'            => $amount,
                'destination'       => '',
                'status'            => $this->getStatusString($status),
                'card_last_digits'  => $payment ? substr($cardNumber, -4) : ''
            );

        } catch (Exception $e) {
            \Log::error($e->getMessage().$e->getTraceAsString());
            return $this->responseApiError('gateway_cielo.retrieve_fail');
        }
    }

    /**
     * Cancel and returns transaction information on Geteway Cielo
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

            $responseRetrieve = $this->retrieve($transaction, $payment);

            if(
                !$responseRetrieve['success'] || 
                (
                    $responseRetrieve['status'] == self::PAYMENT_DENIED   ||
                    $responseRetrieve['status'] == self::PAYMENT_VOIDED   ||
                    $responseRetrieve['status'] == self::PAYMENT_REFUNDED ||
                    $responseRetrieve['status'] == self::PAYMENT_ABORTED
                )
            )
                return $this->responseApiError('gateway_cielo.refund_retrieve_fail');


            // cancela a venda
            $sale           =   $this->cieloEcommerce->cancelSale($transaction->gateway_transaction_id);
            $refundStatus   =   $sale->getStatus();

            if($refundStatus != self::CODE_VOIDED && $refundStatus != self::CODE_REFUNDED)
			{
                return $this->responseApiError('gateway_cielo.refund_fail');
			}

            return array(
                "success" 					=> true ,
                "status" 					=> self::PAYMENT_REFUNDED,
                "transaction_id"			=> $transaction->gateway_transaction_id                    
            );

        } catch (CieloRequestException $e) {
            $error = $e->getCieloError();
            if(!$error)
                $error = $e;
            \Log::error($error->getMessage());
            return $this->responseApiError('gateway_cielo.refund_fail');
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
		return 0.0254;
	}

    /**
     * Fee used on system transaction
     */
	public function getGatewayFee()
	{
		return 0;
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
            "transaction_id"    => '',
            "paid"              => false
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
            case self::CODE_NOTFINISHED:
                return self::PAYMENT_NOTFINISHED;
            case self::CODE_AUTHORIZED:
                return self::PAYMENT_AUTHORIZED;
            case self::CODE_CONFIRMED:
                return self::PAYMENT_CONFIRMED;
            case self::CODE_DENIED:
                return self::PAYMENT_DENIED;
            case self::CODE_VOIDED:
                return self::PAYMENT_VOIDED;
            case self::CODE_REFUNDED:
                return self::PAYMENT_REFUNDED;
            case self::CODE_PENDING:
                return self::PAYMENT_PENDING;
            case self::CODE_ABORTED:
                return self::PAYMENT_ABORTED;
            case self::CODE_SCHEDULED:
                return self::PAYMENT_SCHEDULED;
            default:
                return 'not_geted';
        }
    }

    /**
     * Returns confirmed debit transaction information on Geteway Cielo
     *
     * @param Object        $payment        Object that represents requester card.
     * @param Decimal       $amount         Decimal that represents amount paied on debit card.
     * @param String        $description    String that represents details of transaction.
     *
     * @return Array       [
     *                      'success',
	 *                      'captured',
	 *                      'paid',
	 *                      'status',
	 *                      'transaction_id'
     *                     ]
     */
    public function debit(Payment $payment, $amount, $description)
    {
        $responseConf = $this->setApiKey();
        if(isset($responseConf['success']) && !$responseConf['success'])
            return $responseConf;

        $amount = floor($amount * 100);

        if($amount <= 0)
            return $this->responseApiError('gateway_cielo.amount_negative');

        $cardNumber 			= $payment->getCardNumber();
        $cardExpirationMonth 	= $payment->getCardExpirationMonth();
        $cardExpirationYear 	= $payment->getCardExpirationYear();
        $cardCvv 				= $payment->getCardCvc();
        $cardHolder 			= $payment->getCardHolder();

        if($cardExpirationYear / 100 < 1)
            $cardExpirationYear += 2000;

        $cardType = strtolower($payment->card_type) == "mastercard" ? 'master' : $payment->card_type;

        $sale = new Sale(Uuid::uuid4()->toString());

        // Crie uma instância de Customer informando o nome do cliente
        $customer = $sale->customer($cardHolder);

        // Crie uma instância de Payment informando o valor do pagamento
        $payment = $sale->payment($amount);

        // Crie uma instância de Debit Card utilizando os dados de teste
        // esses dados estão disponíveis no manual de integração
        $payment->debitCard($cardCvv, $cardType)
                ->setExpirationDate(str_pad($cardExpirationMonth, 2, '0', STR_PAD_LEFT) . "/" . $cardExpirationYear)
                ->setCardNumber($cardNumber)
                ->setHolder($cardHolder);

        // Crie o pagamento na Cielo
        try {
            $sale = $this->cieloEcommerce->createSale($sale);

            // Com a venda criada na Cielo, já temos o ID do pagamento, TID e demais
            // dados retornados pela Cielo
            $paymentId      =   $sale->getPayment()->getPaymentId();
            $debitStatus   =   $sale->getPayment()->getStatus();

            if($debitStatus != self::CODE_CONFIRMED)
			{
                return $this->responseApiError('paymentError.refused');
			}

			return array (
				'success'        => true,
				'captured'       => false,
				'paid'           => true,
				'status'         => self::PAYMENT_CONFIRMED,
				'transaction_id' => $paymentId
            );

        } catch (CieloRequestException $e) {
            $error = $e->getCieloError();
            if(!$error)
                $error = $e;
            \Log::error($error->getMessage());
            return $this->responseApiError('gateway_cielo.debit_fail');
        }
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
            "transaction_id" 	=> '',
            "paid"              => false
        );
    }

    /**
    * Converte string do estado para sigla
    * @param string $state
    * @return string
    */
    private function convertStateString($state)
    {
       switch (strtoupper($state)) {
           case "ACRE":
               $state = "AC";
               break;
           case "ALAGOAS":
               $state = "AL";
               break;
           case "AMAZONAS":
               $state = "AM";
               break;
           case "AMAPÁ":
               $state = "AP";
               break;
           case "BAHIA":
               $state = "BA";
               break;
           case "CEARÁ":
               $state = "CE";
               break;
           case "DISTRITO FEDERAL":
               $state = "DF";
               break;
           case "ESPÍRITO SANTO":
               $state = "ES";
               break;
           case "GOIÁS":
               $state = "GO";
               break;
           case "MARANHÃO":
               $state = "MA";
               break;
           case "MINAS GERAIS":
               $state = "MG";
               break;
           case "MATO GROSSO DO SUL":
               $state = "MS";
               break;
           case "MATO GROSSO":
               $state = "MT";
               break;
           case "PARÁ":
               $state = "PA";
               break;
           case "PARAÍBA":
               $state = "PB";
               break;
           case "PERNAMBUCO":
               $state = "PE";
               break;
           case "PIAUÍ":
               $state = "PI";
               break;
           case "PARANÁ":
               $state = "PR";
               break;
           case "RIO DE JANEIRO":
               $state = "RJ";
               break;
           case "RIO GRANDE DO NORTE":
               $state = "RN";
               break;
           case "RONDÔNIA":
               $state = "RO";
               break;
           case "RORAIMA":
               $state = "RR";
               break;
           case "RIO GRANDE DO SUL":
               $state = "RS";
               break;
           case "SANTA CATARINA":
               $state = "SC";
               break;
           case "SERGIPE":
               $state = "SE";
               break;
           case "SÃO PAULO":
               $state = "SP";
               break;
           case "TOCANTÍNS":
               $state = "TO";
               break;
           default:
               break;
       }
   
       return $state;
   }
}
