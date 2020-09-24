<?php

namespace Codificar\PaymentGateways\Omnipay;

use ApiErrors;
use Bank;
use Carbon\Carbon;
use Exception;
use PagarMe;
use PagarMe_Card;
use PagarMe_Exception;
use PagarMe_Recipient;
use PagarMe_Transaction;
//models do sistema
use Payment;
use Provider;
use Transaction;
use User;
use LedgerBankAccount;
use Omnipay\Omnipay;
use Omnipay\Pagarme\CreditCard;
use Settings;

class PagarmeLib implements IGateway
{
	const SPLIT_TYPE_AMOUNT 		= 'amount';
	const SPLIT_TYPE_PERCENTAGE 	= 'percentage';

	const PAGARME_PAID 				= 'paid';
	const PAGARME_PROCESSING 		= 'processing';
	const PAGARME_AUTHORIZED 		= 'authorized';
	const PAGARME_REFUNDED 			= 'refunded';
	const PAGARME_WAITING 			= 'waiting_payment';
	const PAGARME_PENDING_REFUND 	= 'pending_refund';
	const PAGARME_REFUSED 			= 'refused';

	const AUTO_TRANSFER_PROVIDER = 'auto_transfer_provider_payment';

	private $gateway;
	private $parameters;

	public function __construct($omnipay, $parameters = [])
	{
		//create gateway
		$this->gateway = Omnipay::create($omnipay);

		//store data
		$this->parameters = $parameters;

		//inicializa gateway
		$this->initialize();
	}

	private function initialize()
	{
		// Initialise the gateway
		$this->gateway = $this->gateway->initialize($this->parameters);
	}

	/*
	 * Método para criar cartao 
     * @return array
     */
	public function createCard(Payment $payment, $user = null)
	{
		try {

			//recupera user
			if (!$user) {
				$user = $payment->User;
			}

			$cardNumber 			= $payment->getCardNumber();
			$cardExpirationMonth 	= $payment->getCardExpirationMonth();
			$cardExpirationYear 	= $payment->getCardExpirationYear();
			$cardCvv 				= $payment->getCardCvc();
			$cardHolder 			= $payment->getCardHolder();

			$cardExpirationYear = $cardExpirationYear % 100;

			$arrCreditCard = array(
				'firstName'    => $user->first_name,
				'lastName'     => $user->last_name,
				'holder_name'  => $cardHolder,
				'number'       => $cardNumber,
				'expiryMonth'  => str_pad($cardExpirationMonth, 2, '0', STR_PAD_LEFT),
				'expiryYear'   => str_pad($cardExpirationYear, 2, '0', STR_PAD_LEFT),
				'cvv'          => $cardCvv,
				'email'        => $user->email,
				'holder_document_number' => $user->document // CPF or CNPJ
			);

			// Create a credit card object
			// This card can be used for testing.
			$card = new CreditCard($arrCreditCard);

			//gera card
			$response = $this->gateway->createCard(array(
				'card'              => $card,
			))->send();

			//retorno
			if ($response->isSuccessful()) {

				$data = $response->getData();

				$return = array(
					"success" 					=> true,
					"token" 					=> $data['id'],
					"card_token" 				=> $data['id'],
					"customer_id" 				=> $data['id'],
					"card_type" 				=> strtolower($data['brand']),
					"last_four" 				=> $data['last_digits'],
				);

				return $return;
			}
		} catch (PagarMe_Exception  $ex) {

			\Log::error($ex->getMessage());

			return array(
				"success" 					=> false,
				"type" 						=> $ex->getMessage(),
				"code" 						=> $ex->getReturnCode(),
				"message" 					=> $ex->getReturnCode() ? trans("paymentError." . $ex->getReturnCode()) : $ex->getMessage(),
			);
		}
	}

	/*
	 * Método que realiza cobrança no cartão do usuário sem repassar valor algum ao prestador
     * @return array
     */
	public function charge(Payment $payment, $amount, $description, $capture = true, $user = null)
	{
		try {

			$capture = false;

			//recupera user
			if (!$user) {
				$user = $payment->User;
			}

			$dataCharge = array(
				'amount'           => $amount,
				'soft_descriptor'  => $description,
				'payment_method'   => 'credit_card',
				'cardReference' 	=> 	$payment->card_token
			);

			if ($capture) {
				//realiza charge já captura
				$transaction = $this->gateway->purchase($dataCharge);
			} else {
				//autoriza pagamento para capturar depois
				$transaction = $this->gateway->authorize($dataCharge);
			}

			//envia transacao
			$response = $transaction->send();

			//verifica se falhou
			if (!$response->isSuccessful()) {
				return array(
					"success" 					=> false,
					"type" 						=> 'api_charge_error',
					"code" 						=> 'api_charge_error',
					"message" 					=> trans("paymentError.refused"),
					"transaction_id"			=> null //$pagarMeTransaction->id
				);
			}

			//recupera retorno
			$data = $response->getData();
			$return = array(
				'success' => true,
				'captured' => $capture,
				'paid' => ($data['status'] == self::PAGARME_PAID),
				'status' => $data['status'],
				'transaction_id' => $data['id']
			);

			return $return;
		} catch (PagarMe_Exception $ex) {
			\Log::error($ex->getMessage());

			return array(
				"success" 					=> false,
				"type" 						=> 'api_charge_error',
				"code" 						=> $ex->getReturnCode(),
				"message" 					=> $ex->getMessage(),
				"transaction_id"			=> ''
			);
		}
	}

	/*
	 * Método que realiza no cartão do usuário com repasse ao prestador
     * @return array
     */
	public function chargeWithSplit(Payment $payment, Provider $provider, $totalAmount, $providerAmount, $description, $capture = true, User $user = null)
	{
		try {

			$admin_value 	= $totalAmount - $providerAmount;
			$admin_value 	= round($admin_value * 100);
			$providerAmount = round($providerAmount * 100);

			if ($admin_value + $providerAmount == (round($totalAmount * 100)))
				$totalAmount =  round($totalAmount * 100);
			else if ($admin_value + $providerAmount == (ceil($totalAmount * 100)))
				$totalAmount =  ceil($totalAmount * 100);
			else if ($admin_value + $providerAmount == (floor($totalAmount * 100)))
				$totalAmount =  floor($totalAmount * 100);

			$bank_account = LedgerBankAccount::where("provider_id", "=", $provider->id)->first();

			//$parameters = $this->gateway->getParameters();


			$token = "tok_visa";
			$destination =  array(
				"amount"        => $providerAmount,
				"account"       => $provider->getBankAccount()->recipient_id,
			);
			//dd($parameters);
			// Do a purchase transaction on the gateway
			$transaction = $this->gateway->purchase(array(
				'amount'           => $totalAmount,
				'soft_descriptor'  => $description,
				'payment_method'   => 'credit_card',
				'currency' => 'brl',
				'token' => $token,
				'destination' => $destination,
				//'cardReference'    => $payment->card_token,
			));

			/* $transaction = $transaction->setSplitRules(array(
				//prestador
				array(
					"recipient_id" 			=> 	$bank_account->recipient_id,
					"amount"	 			=>  $providerAmount,
					"charge_processing_fee" => 	self::getReversedProcessingFeeCharge() ? true : false,
					"liable" => true  //assume risco de transação (possíveis estornos)
				),
				//admin
				array(
					"recipient_id" => Settings::findByKey('pagarme_recipient_id'),
					"amount" =>  $admin_value,
					"charge_processing_fee" => self::getReversedProcessingFeeCharge() ? false : true, //responsável pela taxa de processamento
					"liable" => true  //assume risco da transação (possíveis estornos)
				)
			)); */

			//	dd($transaction);
			$response = $transaction->send();

			dd($response);


			if (!$response->isSuccessful()) {
				return array(
					"success" 					=> false,
					"type" 						=> 'api_charge_error',
					"code" 						=> 'api_charge_error',
					"message" 					=> trans("paymentError.refused"),
					"transaction_id"			=> null
				);
			}

			$data = $response->getData();

			return array(
				'success' => true,
				'captured' => $capture,
				'paid' => ($data['status'] == self::PAGARME_PAID),
				'status' => $data['status'],
				'transaction_id' => $data['id']
			);
		} catch (PagarMe_Exception $ex) {
			\Log::error($ex->getMessage());

			return array(
				"success" 					=> false,
				"type" 						=> 'api_charge_error',
				"code" 						=> $ex->getReturnCode(),
				"message" 					=> trans("paymentError." . $ex->getReturnCode()),
				"transaction_id"			=> ''
			);
		}
	}

	/*
	 * Método que realiza a captura de uma valor pre-autorizado
     * @return array
     */
	public function capture(Transaction $transaction, $amount, Payment $payment = null)
	{
		try {

			//estrutura a captura
			$capture = $this->gateway->capture(array(
				'amount'        => $amount,
				'currency'      => Settings::getCurrency(),
			));

			//seta a transação
			$capture->setTransactionReference($transaction->gateway_transaction_id);

			//realiza a captura
			$response = $capture->send();

			if (!$response->isSuccessful()) {
				return array(
					"success" 					=> false,
					"type" 						=> 'api_charge_error',
					"code" 						=> 'api_charge_error',
					"message" 					=> trans("paymentError.refused"),
					"transaction_id"			=> null //$pagarMeTransaction->id
				);
			}

			//recupera dados
			$data = $response->getData();
			return array(
				'success' => true,
				'status' => $data['status'],
				'captured' => $data['captured'],
				'paid' => $data['paid'],
				'transaction_id' => $data['id']
			);
		} catch (PagarMe_Exception $ex) {
			\Log::error($ex->getMessage());

			return array(
				"success" 					=> false,
				"type" 						=> 'api_capture_error',
				"code" 						=> $ex->getReturnCode(),
				"message" 					=> trans("paymentError." . $ex->getReturnCode()),
				"transaction_id"			=> $transaction->gateway_transaction_id
			);
		}
	}

	/*
	 * Método que estorna o valor de uma transação
     * @return array
     */
	public function refund(Transaction $transaction, Payment $payment = null)
	{

		if ($transaction && $transaction->status != Transaction::REFUNDED) {

			try {

				$transaction = $this->gateway->refund(array(
					'transactionReference'     => $transaction->gateway_transaction_id,
				));
				$response = $transaction->send();

				if ($response->isSuccessful()) {

					$data = $response->getData();

					return array(
						"success" 			=> true,
						"status" 			=> $data['status'],
						"transaction_id" 	=> $data['id'],
					);
				}
			} catch (Exception $ex) {

				\Log::error($ex->__toString());

				return array(
					"success" 			=> false,
					"type" 				=> 'api_refund_error',
					"code" 				=> $ex->getCode(),
					"message" 			=> $ex->getMessage(),
					"transaction_id" 	=> $refund->id,
				);
			}
		} else {
			$error = array(
				"success" 			=> false,
				"type" 				=> 'api_refund_error',
				"code" 				=> 1,
				"message" 			=> trans("paymentError.noTrasactionRefundFound"),
				"transaction_id" 	=> null,
			);

			\Log::error(print_r($error, 1));

			return $error;
		}
	}

	/*
	 * Método que estorna o valor de uma transação com split
     * @return array
     */
	public function refundWithSplit(Transaction $transaction, Payment $payment)
	{
		\Log::debug('refund with split');

		return ($this->refund($transaction, $payment));
	}

	/*
	 * Método que captura um valor pre-autorizado com split
     * @return array
     */
	public function captureWithSplit(Transaction $transaction, Provider $provider, $totalAmount, $providerAmount, Payment $payment = null)
	{

		try {
			\Log::debug('capture with split');

			$adminAmount = $totalAmount - $providerAmount;

			$pagarMeTransaction = PagarMe_Transaction::findById($transaction->gateway_transaction_id);

			if ($pagarMeTransaction == null)
				throw new PagarMe_Exception("Transaction not found.", 1);

			//criar regra de split e capturar valores
			$param = array(
				"amount" 		=> floor($totalAmount * 100),
				"split_rules" 	=> array(
					//prestador
					array(
						"recipient_id" 						=> $provider->getBankAccount()->recipient_id,
						"amount" 							=> $providerAmount * 100,
						"charge_processing_fee" 			=> $this->getReversedProcessingFeeCharge() ? true : false,
						"liable" 							=> true  //assume risco de transação (possíveis estornos)
					),
					//admin
					array(
						"recipient_id" 						=> self::getRecipientId(),
						"amount" 							=> $adminAmount * 100,
						"charge_processing_fee" 			=> $this->getReversedProcessingFeeCharge() ? false : true, //responsável pela taxa de processamento
						"liable" 							=> true  //assume risco da transação (possíveis estornos)
					)
				)
			);

			$pagarMeTransaction->capture($param);

			return array(
				'success' => true,
				'status' => $pagarMeTransaction->status,
				'captured' => ($pagarMeTransaction->status == self::PAGARME_PAID),
				'paid' => ($pagarMeTransaction->status == self::PAGARME_PAID),
				'transaction_id' => $pagarMeTransaction->id
			);
		} catch (PagarMe_Exception $ex) {
			\Log::error($ex->getMessage());

			return array(
				"success" 					=> false,
				"type" 						=> 'api_capture_error',
				"code" 						=> $ex->getReturnCode(),
				"message" 					=> trans("paymentError." . $ex->getReturnCode()),
				"transaction_id"			=> $transaction->gateway_transaction_id
			);
		}
	}

	/*
	 * Método que recupera transação
     * @return array
     */
	public function retrieve(Transaction $transaction, Payment $payment = null)
	{
		//recupera transação
		$retrieve = $this->gateway->fetchTransaction();
		$retrieve->setTransactionReference($transaction->gateway_transaction_id);
		$response = $retrieve->send();

		//recupera dados
		$data = $response->getData();

		//retorno
		return array(
			'success' => true,
			'transaction_id' => $data['id'],
			'amount' => $data['amount'],
			'destination' => '',
			'status' => $data['status'],
			'card_last_digits' => $data['card']['last_digits'],
		);
	}

	public function getNextCompensationDate()
	{
		$carbon = Carbon::now();
		$carbon->addDays(31);
		return $carbon;
	}

	//retorna os recebíveis de uma transação
	private static function get_transaction_payables($transaction_id)
	{
		try {
			$url = sprintf(
				'https://api.pagar.me/1/transactions/%s/payables?api_key=%s',
				$transaction_id,
				Settings::findByKey('pagarme_api_key')
			);

			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_TIMEOUT, 5);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			$data = curl_exec($ch);
			curl_close($ch);
			return json_decode($data);
		} catch (Exception $ex) {
			throw $ex;
		}
	}


	public function createOrUpdateAccount(LedgerBankAccount $ledgerBankAccount)
	{

		$return = [];
		$recipient = null;

		$bank = Bank::where('id', $ledgerBankAccount->bank_id)->first();

		$settingTransferInterval 	= Settings::findByKey('provider_transfer_interval');
		$settingTransferDay 		= Settings::findByKey('provider_transfer_day');

		/**
		 * Se recipient_id não iniciar com re_ (padrão do Pagar.me) não manda recupear as informações
		 * do recipient_id e cria uma nova conta bancária.
		 */
		if (($ledgerBankAccount->recipient_id) && strpos($ledgerBankAccount->recipient_id, "re_") === 0) {
			$recipient = PagarMe_Recipient::findById($ledgerBankAccount->recipient_id);
		} else {
			$ledgerBankAccount->recipient_id = null;
		}

		try {
			$bankAccount = array(
				"bank_code" 		=> $bank->code,
				"agencia" 			=> $ledgerBankAccount->agency,
				"agencia_dv" 		=> $ledgerBankAccount->agency_digit,
				"conta" 			=> $ledgerBankAccount->account,
				"type"				=> $ledgerBankAccount->account_type,
				"conta_dv" 			=> $ledgerBankAccount->account_digit,
				"document_number" 	=> $ledgerBankAccount->document,
				"legal_name" 		=> $ledgerBankAccount->holder
			);

			$recipientData = array(
				"transfer_interval" => $settingTransferInterval ? $settingTransferInterval : "daily",
				"transfer_day" 		=> $settingTransferDay ? $settingTransferDay : "5",
				"transfer_enabled" 	=> true, //recebe pagamento automaticamente
				"id"				=> $ledgerBankAccount->recipient_id,
				"bank_account" 		=> $bankAccount
			);

			\Log::info("[PagarMe_Recipient] Entrada: " . print_r($recipientData, 1));

			if (!$recipient) {
				$recipient = new PagarMe_Recipient($recipientData);
				$recipient->create();
			} else if ($recipient && $recipient->id) {

				/**
				 * Para atualizar conta bancária é utilizado as funções SET presentes em PagarMe_Recipient.
				 */

				$recipient->setTransferIntervel($settingTransferInterval ? $settingTransferInterval : "daily");
				$recipient->setTransferDay($settingTransferDay ? $settingTransferDay : "5");
				$recipient->setBankAccount($bankAccount);
				$recipient->save();
			}

			\Log::info("[PagarMe_Recipient] Saida: " . print_r($recipientData, 1));


			if ($recipient->id == null) {
				$return['recipient_id'] = $recipient[0]->id;
			} else {
				$return['recipient_id'] = $recipient->id;
			}

			return array(
				"success" 					=> true,
				"recipient_id" 				=> $return['recipient_id']
			);
		} catch (PagarMe_Exception  $ex) {

			\Log::error($ex->__toString());

			$return = array(
				"success" 					=> false,
				"recipient_id"				=> 'empty',
				"type" 						=> 'api_bankaccount_error',
				"code" 						=> $ex->getReturnCode(),
				"message" 					=> trans("empty." . $ex->getMessage())
			);

			return $return;
		}
	}

	/*
	 * Método que deleta cartao do usuario
     * @return array
     */
	public function deleteCard(Payment $payment, User $user = null)
	{
		try {

			//recupera user
			if (!$user) {
				$user = $payment->User;
			}

			//estrutura remoção de cartao
			$response = $this->gateway->deleteCard(array(
				'cardReference' 	=> 	$payment->card_token,
				//'customerReference' => $payment->customer_id
			))->send();

			//verifica se falhou
			if (!$response->isSuccessful()) {
				return array(
					"success" 					=> false
				);
			}

			//retorno ok
			return array(
				"success" 	=> true
			);
		} catch (PagarMe_Exception $ex) {
			$body = $ex->getJsonBody();
			$error = $body['error'];

			if (array_key_exists('code', $body)) $code = $body["code"];
			else $code = null;

			return array(
				"success" 	=> false,
				'data' => null,
				'error' => array(
					"code" 		=> ApiErrors::CARD_ERROR,
					"messages" 	=> array(trans('creditCard.' . $error["code"]))
				)
			);
		}
	}

	public static function getRecipientId()
	{
		return Settings::findByKey('pagarme_recipient_id');
	}

	public function getGatewayTax()
	{
		return 0.0399;
	}

	public function getGatewayFee()
	{
		return 0.5;
	}

	private static function getReversedProcessingFeeCharge()
	{
		return true;
	}

	private function getBilling($user)
	{
		return  array(
			"address" => array(
				"street" => $user->getStreet(),
				"street_number" => $user->getStreetNumber(),
				"neighborhood" => $user->getNeighborhood(),
				"city" => $user->address_city,
				"state" => $user->state,
				"zipcode" => $user->getZipcode(),
				"country" => $user->country
			),
			"name" => $user->getFullName()
		);
	}

	private function getItems($id, $description, $amount)
	{
		$item = array(
			"id" => $id,
			"title" => $description,
			"unit_price" => $amount,
			"quantity" => 1,
			"tangible" => false
		);

		$items[] = $item;

		return $items;
	}
	public function checkAutoTransferProvider()
	{
		try {
			if (Settings::findByKey(self::AUTO_TRANSFER_PROVIDER) == "1")
				return (true);
			else
				return (false);
		} catch (Exception $ex) {
			\Log::error($ex);

			return (false);
		}
	}

	private function getCustomer(Payment $payment)
	{
		$user = $payment->User;

		$customer = array(
			"name" 				=> $user->getFullName(),
			"document_number" 	=> $user->document,
			"email" 			=> $user->email,
			"address" 		=> array(
				"street" 		=> $user->getStreet(),
				"neighborhood" 	=> $user->getNeighborhood(),
				"zipcode" 		=> $user->getZipcode(),
				"street_number" => $user->getStreetNumber()
			),
			"phone" 	=> array(
				"ddd" 		=> $user->getLongDistance(),
				"number" 	=> $user->getPhoneNumber()
			)
		);

		return $customer;
	}
}
