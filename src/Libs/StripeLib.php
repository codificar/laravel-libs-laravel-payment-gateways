<?php

namespace Codificar\PaymentGateways\Libs;

use ApiErrors;
use Carbon\Carbon;

//models do sistema
use Payment;
use Provider;
use Transaction;
use User;
use LedgerBankAccount;
use Settings;

class StripeLib implements IPayment
{
	/* Stripe Error Types */
	const API_CONNECTION_ERROR 	= 'api_connection_error';	// Failure to connect to Stripes API.
	const API_ERROR 			= 'api_error';				// API errors cover any other type of problem (e.g., a temporary problem with Stripes servers) and are extremely uncommon.
	const AUTHENTICATION_ERROR 	= 'authentication_error';	// Failure to properly authenticate yourself in the request.
	const CARD_ERROR 			= 'card_error';				// Card errors are the most common type of error you should expect to handle. They result when the user enters a card that cant be charged for some reason.
	const INVALID_REQUEST_ERROR = 'invalid_request_error';	// Invalid request errors arise when your request has invalid parameters.
	const RATE_LIMIT_ERROR 		= 'rate_limit_error';		// Too many requests hit the API too quickly.
	const VALIDATION_ERROR 		= 'validation_error';		// Errors triggered by our client-side libraries when failing to validate fields (e.g., when a card number or expiration date is invalid or incomplete).

	/* Stripe Error Codes */
	const INVALID_NUMBER 		= 'invalid_number';			// The card number is not a valid credit card number.
	const INVALID_EXPIRY_MONTH 	= 'invalid_expiry_month';	// The card's expiration month is invalid.
	const INVALID_EXPIRY_YEAR 	= 'invalid_expiry_year';	// The card's expiration year is invalid.
	const INVALID_CVC 			= 'invalid_cvc';			// The card's security code is invalid.
	const INVALID_SWIPE_DATA 	= 'invalid_swipe_data';		// The card's swipe data is invalid.
	const INCORRECT_NUMBER 		= 'incorrect_number';		// The card number is incorrect.
	const EXPIRED_CARD 			= 'expired_card';			// The card has expired.
	const INCORRECT_CVC 		= 'incorrect_cvc';			// The card's security code is incorrect.
	const INCORRECT_ZIP 		= 'incorrect_zip';			// The card's zip code failed validation.
	const CARD_DECLINED 		= 'card_declined';			// The card was declined.
	const MISSING 				= 'missing';				// There is no card on a customer that is being charged.
	const PROCESSING_ERROR 		= 'processing_error';		// An error occurred while processing the card.

	const NO_CONNECT 			= 'no_connect';
	const CUSTOM_ACCOUNTS 		= 'custom_accounts';
	const EXPRESS_ACCOUNTS 		= 'express_accounts';
	const STANDARD_ACCOUNTS 	= 'standard_accounts';

	const AUTO_TRANSFER_PROVIDER = 'auto_transfer_provider_payment';

	public function __construct()
	{
		$this->setApiKey();
	}

	private function setApiKey()
	{
		\Stripe\Stripe::setApiKey(Settings::findByKey('stripe_secret_key'));
	}

	public function createCard(Payment $payment, $user = null)
	{

		$cardNumber 			= $payment->getCardNumber();
		$cardExpirationMonth 	= $payment->getCardExpirationMonth();
		$cardExpirationYear 	= $payment->getCardExpirationYear();
		$cardCvc 				= $payment->getCardCvc();
		$cardHolder 			= $payment->getCardHolder();
		$stripeCustomer 		= null;
		$createdCard 			= null;

		try {
			// captura o usuario
			$user = $payment->User;
			// verifica se e um cartao stripe
			if (self::isCustomerIdFromStripe($payment->customer_id)) {
				// verifica se existe
				$stripeCustomer = \Stripe\Customer::retrieve($payment->customer_id);

				// atualiza cartao e outros dados
				if ($stripeCustomer && $stripeCustomer->id) {
					// adiciona a nova fonte de cartao
					$createdCard = $stripeCustomer->sources->create(
						array(
							"card" => array(
								"number" 	=> $cardNumber,
								"exp_month" => $cardExpirationMonth,
								"exp_year" 	=> $cardExpirationYear,
								"cvc"	 	=> $cardCvc,
								"name"	 	=> $cardHolder
							)
						)
					);

					$stripeCustomer->description 	= $user->getFullName();
					$stripeCustomer->email 			= $user->email;
					$stripeCustomer->save();
				}
			}

			if (!$stripeCustomer) {
				/**
				 * Create Stripe Credit Card with Token (safe transaction).
				 */

				$card_Token = $this->createToken($cardNumber, $cardExpirationMonth, $cardExpirationYear, $cardCvc, $cardHolder);

				$stripeCustomer = \Stripe\Customer::create(array(
					"source"		=> $card_Token["token"],
					"description" 	=> $user->getFullName(),
					"email" 		=> $user->email
				));

				if ($stripeCustomer->sources) {
					$createdCard = $stripeCustomer->sources["data"][0];
				} else {
					return array(
						"success" 	=> false,
						'data' 		=> null,
						'error' 	=> array(
							"code" 		=> ApiErrors::CARD_ERROR,
							"messages" 	=> $stripeCustomer->error['message']
						)
					);
				}
			}

			return array(
				"success" 		=> true,
				"customer_id" 	=> $stripeCustomer->id,
				"card_token" 	=> $createdCard->id,
				"last_four" 	=> $createdCard->last4,
				"card_type"		=> strtolower($createdCard->brand)
			);
		} catch (Stripe\Error\Base $ex) {
			$body = $ex->getJsonBody();
			$error = $body['error'];
			//Log::info(__FUNCTION__.":error". __LINE__);
			//Log::info(print_r($error,1));
			if (array_key_exists('code', $body)) $code = $body["code"];
			else $code = null;

			return array(
				"success" 	=> false,
				'data' => null,
				'error' => array(
					"code" 		=> ApiErrors::CARD_ERROR,
					"messages" 	=> array(trans('creditCard.customerCreationFail'))
				)
			);
		}
	}

	public static function isCustomerIdFromStripe($customerId)
	{
		return !(strpos($customerId, "cus_") === FALSE);
	}

	/**
	 * Reference Link: https://stripe.com/docs/connect/destination-charges
	 * 
	 * Charge the user service using Split.
	 */
	public function chargeWithSplit(Payment $payment, Provider $provider, $totalAmount, $providerAmount, $description, $capture = true, User $user = null)
	{
		// fix amount for payment (Total and provider value)
		$totalAmount 		= round($totalAmount * 100);
		$providerAmount = round($providerAmount * 100);
		$destination = null;

		if ($provider->getBankAccount()) {
			$destination =  array(
				"amount"        => $providerAmount,
				"account"       => $provider->getBankAccount()->recipient_id,
			);
		}

		try {
			$charge = \Stripe\Charge::create(
				array(
					"amount" 		=> $totalAmount,
					"currency" 		=> Settings::getCurrency(),
					"customer" 		=> $payment->customer_id,
					"description" 	=> $description,
					"capture"       => $capture,
					"destination" 	=> $destination
				)
			);

			if ($charge->failure_code) {
				return array(
					"success" 			=> false,
					"type" 				=> 'card_error',
					"code" 				=> $charge->failure_code,
					"message" 			=> trans("paymentError." . $charge->failure_code),
					"transaction_id" 	=> $charge->id,
				);
			}

			return array(
				"success" 			=> true,
				"paid" 				=> $charge->paid,
				"status" 			=> $this->getStatus($charge),
				"captured" 			=> $charge->captured,
				"transaction_id" 	=> $charge->id,
				"status" 			=> $charge->status,
			);
		} catch (Stripe\Error\InvalidRequest $ex) {

			\Log::error($ex->getMessage());

			$body = $ex->getJsonBody();
			$error = $body['error'];
			// Log::info(__FUNCTION__.":error". __LINE__);
			// Log::info(print_r($error,1));
			return array(
				"success" 			=> false,
				"type" 				=> $error["type"],
				"code" 				=> '',
				"message" 			=> $error["message"],
				"transaction_id" 	=> ''
			);
		}
	}

	public function charge(Payment $payment, $amount, $description, $capture = true, $user = null)
	{

		// fix amount for payment
		$amount = round($amount * 100);

		try {
			$charge = \Stripe\Charge::create(
				array(
					"amount" 		=> $amount,
					"currency" 		=> Settings::getCurrency(),
					"customer" 		=> $payment->customer_id,
					"description" 	=> $description,
					"capture"       => $capture
				)
			);

			if ($charge->failure_code) {
				return array(
					"success" 			=> false,
					"type" 				=> 'card_error',
					"code" 				=> $charge->failure_code,
					"message" 			=> trans("paymentError." . $charge->failure_code),
					"transaction_id" 	=> $charge->id,
				);
			}

			return array(
				"success" 			=> true,
				"paid" 				=> $charge->paid,
				"status" 			=> $this->getStatus($charge),
				"captured" 			=> $charge->captured,
				"transaction_id" 	=> $charge->id,
				"status" 			=> $charge->status,
			);
		} catch (Stripe\Error\InvalidRequest $ex) {
			\Log::error($ex->getMessage());

			$body = $ex->getJsonBody();
			$error = $body['error'];

			return array(
				"success" 			=> false,
				"type" 				=> $error["type"],
				"code" 				=> '',
				"message" 			=> $error["message"],
				"transaction_id" 	=> ''
			);
		}
	}

	public function captureWithSplit(Transaction $transaction, Provider $provider, $totalAmount, $providerAmount, Payment $payment = null)
	{
		try {
			$charge = \Stripe\Charge::retrieve($transaction->gateway_transaction_id);

			\Log::debug('Charge: ' . json_encode($charge));

			$providerBankAccount = $provider->getBankAccount()->recipient_id;

			if ($charge->destination != $providerBankAccount) {
				return array(
					"success" 			=> false,
					"type" 				=> 'invalid_provider_bank_account',
					"code" 				=> 'invalid_provider_bank_account',
					"message" 			=> trans('paymentError.invalid_provider_bank_account'),
					"transaction_id" 	=> $charge->id,
				);
			}

			$totalAmount = round($totalAmount * 100);
			$providerAmount = round($providerAmount * 100);

			$charge->destination = $providerBankAccount;
			$charge->amount = $totalAmount;

			\Log::debug('TotalAmount: ' . $totalAmount . ' ProviderAmount: ' . $providerAmount);

			$charge->capture(
				array(
					"amount" => $totalAmount,
					"destination" 	=> array(
						"amount" 	=> $providerAmount
					)
				)
			);

			if ($charge->failure_code) {
				return array(
					"success" 			=> false,
					"type" 				=> 'api_capture_error',
					"code" 				=> $charge->failure_code,
					"message" 			=> trans("paymentError." . $charge->failure_code),
					"transaction_id" 	=> $charge->id,
				);
			}

			return array(
				'success' => true,
				'status' => $this->getStatus($charge),
				'captured' => true,
				'paid' => true,
				'transaction_id' => $charge->id
			);
		} catch (Stripe\Error\InvalidRequest $ex) {
			\Log::error($ex->getMessage());

			$body = $ex->getJsonBody();
			$error = $body['error'];

			return array(
				"success" 			=> false,
				"type" 				=> $error["type"],
				"code" 				=> '',
				"message" 			=> $error["message"],
				"transaction_id" 	=> ''
			);
		}
	}

	public function capture(Transaction $transaction, $amount, Payment $payment = null)
	{

		try {
			$charge = \Stripe\Charge::retrieve($transaction->gateway_transaction_id);

			$chargeResponse = $charge->capture();

			if ($charge->failure_code) {
				return array(
					"success" 			=> false,
					"type" 				=> 'api_capture_error',
					"code" 				=> $charge->failure_code,
					"message" 			=> trans("paymentError." . $charge->failure_code),
					"transaction_id" 	=> $charge->id,
				);
			}

			return array(
				'success' => true,
				'status' => $this->getStatus($charge),
				'captured' => true,
				'paid' => true,
				'transaction_id' => $charge->id
			);
		} catch (Stripe\Error\InvalidRequest $ex) {
			\Log::error($ex->getMessage());

			$body = $ex->getJsonBody();
			$error = $body['error'];

			return array(
				"success" 			=> false,
				"type" 				=> $error["type"],
				"code" 				=> '',
				"message" 			=> $error["message"],
				"transaction_id" 	=> ''
			);
		}
	}


	public function refund(Transaction $transaction, Payment $payment = null)
	{

		try {
			$refund = \Stripe\Refund::create(
				array(
					"charge" => $transaction->gateway_transaction_id
				)
			);

			if ($refund->status == 'failed') {

				return array(
					"success" 			=> false,
					"type" 				=> 'refund_error',
					"code" 				=> 'refund_error',
					"message" 			=> trans("paymentError.refund_failed"),
					"transaction_id" 	=> $refund->id,
				);
			}

			return array(
				"success" 			=> true,
				"status" 			=> 'refunded',
				"transaction_id" 	=> $refund->id,
			);
		} catch (Stripe\Error\InvalidRequest $ex) {
			\Log::error($ex->getMessage());

			$body = $ex->getJsonBody();
			$error = $body['error'];

			return array(
				"success" 			=> false,
				"type" 				=> $error["type"],
				"code" 				=> '',
				"message" 			=> $error["message"],
				"transaction_id" 	=> ''
			);
		}
	}

	/**
	 * Do refund recovering value sent to Provider
	 */
	public function refundWithSplit(Transaction $transaction, Payment $payment = null)
	{

		try {

			if (Settings::getStripeTotalSplitRefund() == true)
				$reverse = true;
			else
				$reverse = false;

			$refund = \Stripe\Refund::create(
				array(
					"charge" => $transaction->gateway_transaction_id,
					"reverse_transfer" => $reverse
				)
			);

			if ($refund->status == 'failed') {

				return array(
					"success" 			=> false,
					"type" 				=> 'refund_error',
					"code" 				=> 'refund_error',
					"message" 			=> trans("paymentError.refund_failed"),
					"transaction_id" 	=> $refund->id,
				);
			}

			return array(
				"success" 			=> true,
				"status" 			=> 'refunded',
				"transaction_id" 	=> $refund->id,
			);
		} catch (Stripe\Error\InvalidRequest $ex) {
			\Log::error($ex->getMessage());

			$body = $ex->getJsonBody();
			$error = $body['error'];

			return array(
				"success" 			=> false,
				"type" 				=> $error["type"],
				"code" 				=> '',
				"message" 			=> $error["message"],
				"transaction_id" 	=> ''
			);
		}
	}

	public function retrieve(Transaction $transaction, Payment $payment = null)
	{

		$stripeTransaction = \Stripe\Charge::retrieve($transaction->gateway_transaction_id);

		return array(
			'success' => true,
			'transaction_id' => $stripeTransaction->id,
			'amount' => $stripeTransaction->amount,
			'destination' => $stripeTransaction->destination,
			'status' => $this->getStatus($stripeTransaction),
			'card_last_digits' => $stripeTransaction->source->last4,
		);
	}

	public static function uploadIdentityDocument($filePath = null, $accountId = null)
	{

		$return = [];

		if (Settings::getStripeConnect() != self::CUSTOM_ACCOUNTS) {
			return array(
				"success" 					=> true,
				"file_token" 				=> null,
			);
		}

		try {
			$uploadData = array(
				"purpose" 	=> "identity_document",
				"file" 		=> fopen($filePath, 'r')
			);
			array("stripe_account" => $accountId);


			$file = null;

			if (!$file) {
				\Stripe\Stripe::setApiKey(Settings::findByKey('stripe_secret_key'));

				$file = \Stripe\FileUpload::create($uploadData);
			} else {
				$file->save($uploadData);
			}

			$return['file_token'] = $file->id;

			//Log::info("acc".$providerDocument->file_token);
			// Log::info("file:".print_r($file->id,1));

			//return $return;

			return array(
				"success" 					=> true,
				"file_token" 				=> $return['file_token'],
			);
		} catch (Stripe\Error\InvalidRequest $ex) {
			$body = $ex->getJsonBody();
			$error = $body['error'];
			return array(
				"success" 					=> false,
				"file_token" 				=> 'empty',
				"type" 						=> $error["type"],
				"message" 					=> $error["message"],


			);
		}
	}

	public function createOrUpdateAccount(LedgerBankAccount $ledgerBankAccount)
	{

		$return = [];

		if (Settings::getStripeConnect() != self::CUSTOM_ACCOUNTS) {
			return array(
				"success" 					=> true,
				"recipient_id"				=> '',
				"message_error"				=> null
			);
		}

		$ledger = $ledgerBankAccount->ledger;

		$provider = $ledger->provider;

		/**
		 * Set default how many days provider will receive cash after service is completed.
		 */
		$time = 7;
		$settingTransferInterval = Settings::where('key', 'provider_transfer_interval')->first();
		if ($settingTransferInterval->value == 'daily')
			$time = 1;
		else if ($settingTransferInterval->value == 'weekly')
			$time = 7;
		else if ($settingTransferInterval->value == 'monthly')
			$time = 30;

		$providerDocument = $provider->getDocumentIdentity();

		$birthday_year = date('Y', strtotime($ledgerBankAccount->birthday_date));
		$birthday_month = date('m', strtotime($ledgerBankAccount->birthday_date));
		$birthday_day = date('d', strtotime($ledgerBankAccount->birthday_date));

		if (!$ledgerBankAccount->birthday_date) {
			return array(
				"success" 					=> false,
				"recipient_id"				=> 'empty',
				"message_error"				=> trans('bank_account.invalid_birthday')
			);
		}

		$accountData = array(
			'type' => 'custom',
			'country' => 'BR',
			'external_account' => array(
				'object' 	=> 'bank_account',
				'country' 	=> 'BR',
				'currency' 	=> 'brl',
				'routing_number' => $ledgerBankAccount->getRoutingNumber(),
				'account_number' => $ledgerBankAccount->getAccountNumber()
			),
			'legal_entity' => array(
				'type'				 => $ledgerBankAccount->person_type,
				'personal_id_number' => preg_replace('/[^0-9]/', '', $ledgerBankAccount->document),
				'dob'                => array('year' => $birthday_year, 'month' => $birthday_month, 'day' => $birthday_day),

				'first_name'         => $provider->first_name,
				'last_name'          => $provider->last_name,
				'address'            => array(
					'line1'          => $provider->address,
					'postal_code'    => $provider->zipcode,
					'city'           => $provider->address_city,
					'state'          => $provider->state
				),
				'personal_address'   => array(
					'line1'          => $provider->address,
					'postal_code'    => $provider->zipcode,
					'city'           => $provider->address_city,
					'state'          => $provider->state

				)
			),
			// "payout_schedule" => array(
			// 	"delay_days" => $time,
			// 	"interval" => "daily"
			// ),
			'tos_acceptance' => array('date' => time(), 'ip' => '127.0.0.1')
		);

		if ($providerDocument) {
			$accountData['legal_entity']['verification'] = array(
				'document' => $providerDocument->file_token
			);
		}

		$account = null;

		\Log::debug(json_encode($accountData));

		/**
		 * Se recipient_id não iniciar com acct_ (padrão do Stripe) não manda recupear as informações
		 * do recipient_id e cria uma nova conta bancária.
		 */
		if (($ledgerBankAccount->recipient_id) && (strpos($ledgerBankAccount->recipient_id, "acct_") === 0)) {
			$account = \Stripe\Account::retrieve($ledgerBankAccount->recipient_id);

			//\Log::debug(print_r($account,1));
		}

		if (!$account) {
			$dataStripe = $account = \Stripe\Account::create($accountData);
		} else {
			$account->external_account = array(
				'object' => 'bank_account',
				'country' => 'BR',
				'currency' => 'brl',
				'routing_number' => $ledgerBankAccount->getRoutingNumber(),
				'account_number' => $ledgerBankAccount->getAccountNumber()
			);

			$birthday_year = date('Y', strtotime($ledgerBankAccount->birthday_date));
			$birthday_month = date('m', strtotime($ledgerBankAccount->birthday_date));
			$birthday_day = date('d', strtotime($ledgerBankAccount->birthday_date));

			if (!$ledgerBankAccount->birthday_date) {
				return array(
					"success" 					=> false,
					"recipient_id"				=> 'empty',
					"message_error"				=> trans('bank_account.invalid_birthday')
				);
			}

			$account->legal_entity->type 							= $ledgerBankAccount->person_type;

			if ($account->legal_entity->verification->status != 'verified')
				$account->legal_entity->personal_id_number 				= preg_replace('/[^0-9]/', '', $ledgerBankAccount->document);

			$account->legal_entity->dob 							= array('year' => $birthday_year, 'month' => $birthday_month, 'day' => $birthday_day);
			$account->legal_entity->first_name 						= $provider->first_name;
			$account->legal_entity->last_name 						= $provider->last_name;

			//Update Address
			$account->legal_entity->address->line1					= $provider->address;
			$account->legal_entity->address->postal_code			= $provider->zipcode;
			$account->legal_entity->address->city					= $provider->address_city;
			$account->legal_entity->address->state					= $provider->state;

			//Update Personal Address
			$account->legal_entity->personal_address->line1			= $provider->address;
			$account->legal_entity->personal_address->postal_code	= $provider->zipcode;
			$account->legal_entity->personal_address->city			= $provider->address_city;
			$account->legal_entity->personal_address->state			= $provider->state;

			//Check if has document and if document is different from the previous one
			if ($providerDocument && ($account->legal_entity->verification->document != $providerDocument->file_token)) {
				$account->legal_entity->verification->document 	= $providerDocument->file_token;
			}

			// if($time >= $account->payout_schedule->delay_days){
			// 	$account->payout_schedule->delay_days = $time;
			// 	$account->payout_schedule->interval = "daily";
			// }

			$account->tos_acceptance = array('date' => time(), 'ip' => '127.0.0.1');

			try {
				$dataStripe = $account->save();
			} catch (Stripe\Error\InvalidRequest $ex) {
				\Log::error($ex);
				$body = $ex->getJsonBody();
				$error = trans('setting.bank_stripe_error');

				if (isset($body['error']['message'])) {
					$error = trans("setting." . str_replace(" ", "_", $body['error']['message']));
				}

				$return = array(
					"success" 					=> false,
					"recipient_id"				=> $account->id,
					"message_error"				=> $error
				);

				return $return;
			}
		}

		if ($account->id) {
			$return = array(
				"success" 					=> true,
				"recipient_id"				=> $account->id,
				"message_error"				=> null
			);
		} else {
			$return = array(
				"success" 					=> false,
				"recipient_id"				=> 'empty',
				"message_error"				=> trans('setting.bank_stripe_error')
			);
		}

		return $return;
	}

	private function createToken($cardNumber, $cardExpirationMonth, $cardExpirationYear, $cardCvc, $cardHolder)
	{

		try {
			$token = \Stripe\Token::create(
				array(
					"card" =>
					array(
						"number" 		=> $cardNumber,
						"exp_month" 	=> $cardExpirationMonth,
						"exp_year" 		=> $cardExpirationYear,
						"cvc"	 		=> $cardCvc,
						"name"	 		=> $cardHolder
					)
				)
			);

			return array(
				"success" 				=> true,
				"token" 				=> $token->id,
				"card_token" 			=> $token->card->id,
				"card_type" 			=> strtolower($token->card->brand),
				"last_four" 			=> $token->card->last4,
			);
		} catch (Stripe\CardError $ex) {
			$body = $ex->getJsonBody();
			return array(
				"success" 				=> false,
				"type" 					=> $body["error"]["type"],
				"code" 					=> $body["error"]["code"],
				"message" 				=> trans("paymentError." . $body["error"]["code"]),
			);
		}
	}

	public function getNextCompensationDate()
	{
		$carbon = Carbon::now();
		$carbon->addDays(31);
		return $carbon;
	}


	public function deleteCard(Payment $payment, User $user = null)
	{
		try {
			self::setApiKey();

			$stripeCustomer = \Stripe\Customer::retrieve($payment->customer_id);
			$stripeCustomer->delete();

			return array(
				"success" 	=> true
			);
		} catch (Stripe\Error\Base $ex) {
			$body = $ex->getJsonBody();
			$error = $body['error'];

			if (array_key_exists('code', $body)) $code = $body["code"];
			else $code = null;

			return array(
				"success" 	=> false,
				'data' => null,
				'error' => array(
					"code" 		=> ApiErrors::CARD_ERROR,
					"messages" 	=> $error['messages']
				)
			);
		}
	}

	public static function verifyStatus($account_id)
	{
		try {
			if ($account_id != null && $account_id != 'empty') {
				self::setApiKey();
				$stripeAccount = \Stripe\Account::retrieve($account_id);
				$verification = $stripeAccount->legal_entity->verification->status;
			} else {
				$verification = 'empty';
			}

			return array(
				"success"	=> true,
				"data"		=> $verification
			);
		} catch (Stripe\Error\Base $ex) {
			$body = $ex->getJsonBody();
			$error = $body['error'];

			if (array_key_exists('code', $body)) $code = $body["code"];
			else $code = null;

			return array(
				"success" 	=> false,
				'data' => null,
				'error' => array(
					"code" 		=> ApiErrors::CARD_ERROR,
					"messages" 	=> $error
				)
			);
		}
	}

	private function getStatus($stripeTransaction)
	{
		$status = 'processing';

		if ($stripeTransaction->refunded) {
			$status = 'refunded';
		} else if ($stripeTransaction->paid) {
			if ($stripeTransaction->captured)
				$status = 'paid';
			else
				$status = 'authorized';
		}

		return ($status);
	}

	public function getGatewayTax()
	{
		return 0.0399;
	}

	public function getGatewayFee()
	{
		return 0.5;
	}

	public function checkAutoTransferProvider()
	{
		try {
			if (Settings::findByKey(self::AUTO_TRANSFER_PROVIDER) == "1" && Settings::getStripeConnect() == self::CUSTOM_ACCOUNTS)
				return (true);
			else
				return (false);
		} catch (Exception $ex) {
			\Log::error($ex);

			return (false);
		}
	}
}
