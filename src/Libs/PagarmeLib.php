<?php

namespace Codificar\PaymentGateways\Libs;

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
use Settings;

class PagarmeLib implements IPayment
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

	public function __construct()
	{
		$this->setApiKey();
	}

	private function setApiKey()
	{
		PagarMe::setApiKey(Settings::findByKey('pagarme_api_key'));
	}

	public function createCard(Payment $payment, $user = null)
	{
		try {

			$cardNumber 			= $payment->getCardNumber();
			$cardExpirationMonth 	= $payment->getCardExpirationMonth();
			$cardExpirationYear 	= $payment->getCardExpirationYear();
			$cardCvv 				= $payment->getCardCvc();
			$cardHolder 			= $payment->getCardHolder();

			$cardExpirationYear = $cardExpirationYear % 100;

			$card = new PagarMe_Card(array(
				"card_number" 				=> $cardNumber,
				"card_holder_name" 			=> $cardHolder,
				"card_expiration_month" 	=> str_pad($cardExpirationMonth, 2, '0', STR_PAD_LEFT),
				"card_expiration_year" 		=> str_pad($cardExpirationYear, 2, '0', STR_PAD_LEFT),
				"card_cvv" 					=> $cardCvv,
			));

			$card->create();

			return array(
				"success" 					=> true,
				"token" 					=> $card->id,
				"card_token" 				=> $card->id,
				"customer_id" 				=> $card->id,
				"card_type" 				=> strtolower($card->brand),
				"last_four" 				=> $card->last_digits,
			);
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

	//realiza cobrança no cartão do usuário sem repassar valor algum ao prestador
	public function charge(Payment $payment, $amount, $description, $capture = true, $user = null)
	{
		try {
			// valor inteiro do pagamento transferido para o admin
			$card = PagarMe_Card::findById($payment->card_token);

			if ($card == null)
				throw new PagarMe_Exception("Cartão não encontrado", 1);

			$pagarMeTransaction = new PagarMe_Transaction(array(
				"amount" 	=> 	floor($amount * 100),
				"card_id" 	=> 	$payment->card_token,
				"capture" 	=> 	boolval($capture),
				"customer" 	=> 	$this->getCustomer($payment),
				"billing"	=> 	$this->getBilling($payment->User),
				"items"		=>  $this->getItems(1, $description, floor($amount * 100))
			));

			\Log::debug("[charge]parameters:" . print_r($pagarMeTransaction, 1));

			$pagarMeTransaction->charge();

			\Log::debug("[charge]response:" . print_r($pagarMeTransaction, 1));

			if ($pagarMeTransaction->status == self::PAGARME_REFUSED) {
				return array(
					"success" 					=> false,
					"type" 						=> 'api_charge_error',
					"code" 						=> 'api_charge_error',
					"message" 					=> trans("paymentError.refused"),
					"transaction_id"			=> $pagarMeTransaction->id
				);
			}

			return array(
				'success' => true,
				'captured' => $capture,
				'paid' => ($pagarMeTransaction->status == self::PAGARME_PAID),
				'status' => $pagarMeTransaction->status,
				'transaction_id' => $pagarMeTransaction->id
			);
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

	//relaliza cobrança no cartão do usuário com repasse ao prestador
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

			if (PagarMe_Recipient::findById(Settings::findByKey('pagarme_recipient_id')) == null)
				throw new PagarMe_Exception("Recebedor do Administrador não foi encontrado. Corrigir no sistema Web.", 1);

			$bank_account = LedgerBankAccount::where("provider_id", "=", $provider->id)->first();

			if ($bank_account == null)
				throw new PagarMe_Exception("Conta do prestador nao encontrada.", 1);

			$recipient = PagarMe_Recipient::findById($bank_account->recipient_id);

			if ($recipient == null)
				throw new PagarMe_Exception("Recebedor não foi encontrado", 1);

			$card = PagarMe_Card::findById($payment->card_token);

			if ($card == null)
				throw new PagarMe_Exception("Cartão não encontrado", 1);

			//split de pagamento com o prestador
			$pagarmeTransaction = new PagarMe_Transaction(array(
				"amount" 		=> 	$totalAmount,
				"card_id" 		=> 	$payment->card_token,
				"capture" 		=> 	boolval($capture),
				"customer" 		=> 	$this->getCustomer($payment),
				"billing"		=> 	$this->getBilling($payment->User),
				"items"			=>  $this->getItems(1, $description, $totalAmount),
				"split_rules" 	=> 	array(
					//prestador
					array(
						"recipient_id" 			=> 	$recipient->id,
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
				)
			));

			\Log::debug("[charge]parameters:" . print_r($pagarmeTransaction, 1));

			$pagarmeTransaction->charge();

			\Log::debug("[charge]response:" . print_r($pagarmeTransaction, 1));

			if ($pagarmeTransaction->status == self::PAGARME_REFUSED) {
				return array(
					"success" 					=> false,
					"type" 						=> 'api_charge_error',
					"code" 						=> 'api_charge_error',
					"message" 					=> trans("paymentError.refused"),
					"transaction_id"			=> $pagarmeTransaction->id
				);
			}

			return array(
				'success' => true,
				'captured' => $capture,
				'paid' => ($pagarmeTransaction->status == self::PAGARME_PAID),
				'status' => $pagarmeTransaction->status,
				'transaction_id' => $pagarmeTransaction->id
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


	public function capture(Transaction $transaction, $amount, Payment $payment = null)
	{
		try {
			$amount *= 100;

			$pagarMeTransaction = PagarMe_Transaction::findById($transaction->gateway_transaction_id);

			if ($pagarMeTransaction == null)
				throw new PagarMe_Exception("Transaction not found.", 1);

			if ($amount > $pagarMeTransaction->amount)
				$amount = $pagarMeTransaction->amount;

			\Log::debug("[capture]parameters:" . print_r($pagarMeTransaction, 1));

			$pagarMeTransaction->capture(floor($amount));

			\Log::debug("[capture]response:" . print_r($pagarMeTransaction, 1));

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

	public function refund(Transaction $transaction, Payment $payment)
	{

		if ($transaction && $transaction->status != Transaction::REFUNDED) {

			try {

				$refund = PagarMe_Transaction::findById($transaction->gateway_transaction_id);

				\Log::debug("[refund]parameters:" . print_r($refund, 1));


				$refund->refund();

				\Log::debug("[refund]response:" . print_r($refund, 1));

				return array(
					"success" 			=> true,
					"status" 			=> $refund->status,
					"transaction_id" 	=> $refund->id,
				);
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

	public function refundWithSplit(Transaction $transaction, Payment $payment)
	{
		\Log::debug('refund with split');

		return ($this->refund($transaction, $payment));
	}

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

	public function retrieve(Transaction $transaction, Payment $payment = null)
	{
		$pagarmeTransaction = PagarMe_Transaction::findById($transaction->gateway_transaction_id);

		return array(
			'success' => true,
			'transaction_id' => $pagarmeTransaction->id,
			'amount' => $pagarmeTransaction->amount,
			'destination' => '',
			'status' => $pagarmeTransaction->status,
			'card_last_digits' => $pagarmeTransaction->card_last_digits,
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

	public function deleteCard(Payment $payment, User $user = null)
	{
		try {

			self::setApiKey();

			/*
			$card = PagarMe_Card::findById($payment->card_token);
			
			if($card)
				$card->delete();
			*/
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
}
