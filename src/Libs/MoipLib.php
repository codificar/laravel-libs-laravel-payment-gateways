<?php

namespace Codificar\PaymentGateways\Libs;
use Carbon\Carbon;

use Payment;
use Provider;
use Transaction;
use User;
use LedgerBankAccount;
use Settings;
use ApiErrors;


class MoipLib extends IPayment{


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

	const NO_CONNECT 			= 'no_connect' ;
	const CUSTOM_ACCOUNTS 		= 'custom_accounts' ;
	const EXPRESS_ACCOUNTS 		= 'express_accounts' ;
	const STANDARD_ACCOUNTS 	= 'standard_accounts' ;

	private static function setApiKey(){
		\Stripe\Stripe::setApiKey(Settings::findByKey('stripe_secret_key'));
	}

	public static function createCard($cardNumber, $cardExpirationMonth, $cardExpirationYear, $cardCvc, $cardHolder){
		self::setApiKey();

		try {
			$token = Stripe\Token::create(
				array(
					"card" =>
					array(
						"number" 		=> $cardNumber,
						"exp_month" 	=> $cardExpirationMonth,
						"exp_year" 		=> $cardExpirationYear,
						"cvc"	 		=> $cardCvc ,
						"name"	 		=> $cardHolder
					)
				)
			);

			return array(
				"success" 				=> true ,
				"token" 				=> $token->id ,
				"card_token" 			=> $token->card->id ,
				"card_type" 			=> strtolower($token->card->brand) ,
				"last_four" 			=> $token->card->last4 ,
				"gateway"				=> "moip"
			);
		}
		catch(Stripe\CardError $ex){
			$body = $ex->getJsonBody();
			return array(
				"success" 				=> false ,
				"type" 					=> $body["error"]["type"] ,
				"code" 					=> $body["error"]["code"] ,
				"message" 				=> trans("paymentError.".$body["error"]["code"]) ,
			);
		}
	}

	public static function chargeBySource($source, $amount, $description, $currency = null, $capture = true){
		self::setApiKey();
		// fix amount for payment
		$amount *= 100 ;
		try {
			$charge = \Stripe\Charge::create(
				array(
					"amount" 		=> $amount,
					"currency" 		=> $currency ? $currency : Settings::getCurrency(),
					"source" 		=> $source,
					"description" 	=> $description,
					"capture"       => $capture
					)
				);

			// Log::info(__FUNCTION__.":charge". __LINE__);
			// Log::info(print_r($charge,1));

			if($charge->failure_code){
				return array(
					"success" 	=> false ,
					"type" 		=> 'card_error' ,
					"code" 		=> $charge->failure_code ,
					"message" 	=> trans("paymentError.".$charge->failure_code),
				);
			}
			else {
				return array(
					"success" 	=> true ,
					"paid" 		=> $charge->paid ,
					"status" 	=> $charge->status ,
					"captured" 	=> $charge->captured ,
				);
			}
		}
		catch(Stripe\Error\InvalidRequest $ex){
			$body = $ex->getJsonBody();
			$error = $body['error'] ;
			return array(
				"success" 	=> false ,
				"type" 		=> $error["type"] ,
				"code" 		=> '' ,
				"message" 	=> $error["message"] ,
			);
		}
	}

	public static function chargeBySourceWithSplit($source, $amount, $providerAmount, $providerBankAccount, $description, $currency = null, $capture = true){
		self::setApiKey();
		// fix amount for payment
		$amount 		= round($amount*100);
		$providerAmount = round($providerAmount*100);
		try {
			$charge = \Stripe\Charge::create(
				array(
					"amount" 		=> $amount,
					"currency" 		=> $currency ? $currency : Settings::getCurrency(),
					"source" 		=> $source,
					"description" 	=> $description,
					"capture"       => $capture,
					"destination" 	=> array(
						"amount" 	=> $providerAmount,
						"account" 	=> $providerBankAccount,
					)
				)
			);

			// Log::info(__FUNCTION__.":charge". __LINE__);
			// Log::info(print_r($charge,1));

			if($charge->failure_code){
				return array(
					"success" 	=> false ,
					"type" 		=> 'card_error' ,
					"code" 		=> $charge->failure_code ,
					"message" 	=> trans("paymentError.".$charge->failure_code),
				);
			}
			else {
				return array(
					"success" 	=> true ,
					"paid" 		=> $charge->paid ,
					"status" 	=> $charge->status ,
					"captured" 	=> $charge->captured ,
				);
			}
		}
		catch(Stripe\Error\InvalidRequest $ex){
			$body = $ex->getJsonBody();
			$error = $body['error'] ;
			return array(
				"success" 	=> false ,
				"type" 		=> $error["type"] ,
				"code" 		=> '' ,
				"message" 	=> $error["message"] ,
			);
		}
	}

	public static function createCustomerByPayment(Payment $payment){
		self::setApiKey();

		$cardNumber 			= $payment->getCardNumber();
		$cardExpirationMonth 	= $payment->getCardExpirationMonth();
		$cardExpirationYear 	= $payment->getCardExpirationYear();
		$cardCvc 				= $payment->getCardCvc();
		$cardHolder 			= $payment->getCardHolder();
		$stripeCustomer 		= null ;
		$createdCard 			= null ;

		try {
			// captura o usuario
			$user = $payment->User ;
			// verifica se e um cartao stripe
			if(self::isCustomerIdFromStripe($payment->customer_id)){
				// verifica se existe
				$stripeCustomer = \Stripe\Customer::retrieve($payment->customer_id);

				// atualiza cartao e outros dados
				if($stripeCustomer && $stripeCustomer->id){
					// adiciona a nova fonte de cartao
					$createdCard = $stripeCustomer->sources->create(
										array(
											"card" => array(
												"number" 	=> $cardNumber,
												"exp_month" => $cardExpirationMonth,
												"exp_year" 	=> $cardExpirationYear,
												"cvc"	 	=> $cardCvc ,
												"name"	 	=> $cardHolder
											)
										)
									);

					$stripeCustomer->description 	= $user->getFullName();
					$stripeCustomer->email 			= $user->email ;
					$stripeCustomer->save();

					//Log::info(__FUNCTION__.":isCustomerIdFromStripe:". __LINE__);
					//Log::info(print_r($createdCard,1));
				}
			}

			if(!$stripeCustomer){

				//self::createCustomerWithCardInformation($cardNumber, $cardExpirationMonth, $cardExpirationYear, $cardCvc, $cardHolder);

				/**
				 * Create Stripe Credit Card with Token (safe transaction).
				 */

				$card_Token = self::createCard($cardNumber, $cardExpirationMonth, $cardExpirationYear, $cardCvc, $cardHolder);

				$stripeCustomer = \Stripe\Customer::create(array(
						"source"		=> $card_Token["token"],
						"description" 	=> $user->getFullName() ,
						"email" 		=> $user->email
					)
				);

				if($stripeCustomer->sources){
					$createdCard = $stripeCustomer->sources["data"][0];
				}
				else {
					return array(
						"success" 	=> false ,
						'data' 		=> null,
						'error' 	=> array(
							"code" 		=> ApiErrors::CARD_ERROR,
							"messages" 	=> $stripeCustomer->error['message']
						)
					);
				}

				// Log::info(__FUNCTION__.":createdCard:". __LINE__);
				// Log::info(print_r($createdCard,1));
			}

			//Log::info(__FUNCTION__.":stripeCustomer:". __LINE__);
			//Log::info(print_r($stripeCustomer,1));

			return array(
				"success" 		=> true ,
				"customer_id" 	=> $stripeCustomer->id ,
				"card_token" 	=> $createdCard->id ,
				"last_four" 	=> $createdCard->last4 ,
				"card_type"		=> strtolower($createdCard->brand)
			);

		}
		catch (Stripe\Error\Base $ex){
			$body = $ex->getJsonBody();
			$error = $body['error'] ;
			//Log::info(__FUNCTION__.":error". __LINE__);
			//Log::info(print_r($error,1));
			if(array_key_exists('code', $body)) $code = $body["code"];
			else $code = null ;

			return array(
				"success" 	=> false ,
				'data' => null,
				'error' => array(
					"code" 		=> ApiErrors::CARD_ERROR,
					"messages" 	=> array(trans('creditCard.customerCreationFail'))
				)
			);
		}

	}

	public static function createCustomerWithCardInformation($cardNumber, $cardExpirationMonth, $cardExpirationYear, $cardCvc, $cardHolder) {
		$stripeCustomer = \Stripe\Customer::create(array(
			"card" => array(
				"number" 	=> $cardNumber,
				"exp_month" => $cardExpirationMonth,
				"exp_year" 	=> $cardExpirationYear,
				"cvc"	 	=> $cardCvc ,
				"name"	 	=> $cardHolder
			),
			"description" 	=> $user->getFullName() ,
			"email" 		=> $user->email
			)
		);

		return $stripeCustomer;
	}

	public static function isCustomerIdFromStripe($customerId){
		return !(strpos($customerId, "cus_") === FALSE);
	}

	/**
	 * Reference Link: https://stripe.com/docs/connect/destination-charges
	 * 
	 * Charge the user service using Split.
	 */
	public static function chargeByCustomerIdWithSplit($customerId, $amount, $providerAmount, $providerBankAccount, $description, $currency = null, $capture = true)
	{
		self::setApiKey();

		// fix amount for payment (Total and provider value)
		$amount 		= round($amount*100);
		$providerAmount = round($providerAmount*100);

		try {
			$charge = \Stripe\Charge::create(
				array(
					"amount" 		=> $amount,
					"currency" 		=> $currency ? $currency : Settings::getCurrency(),
					"customer" 		=> $customerId,
					"description" 	=> $description,
					"capture"       => $capture,
					"destination" 	=> array(
						"amount" 	=> $providerAmount,
						"account" 	=> $providerBankAccount,
					)
				)
			);

			// Log::info(__FUNCTION__.":charge". __LINE__);
			// Log::info(print_r($charge,1));

			if($charge->failure_code){
				return array(
					"success" 			=> false ,
					"type" 				=> 'card_error' ,
					"code" 				=> $charge->failure_code ,
					"message" 			=> trans("paymentError.".$charge->failure_code),
					"transaction_id" 	=> $charge->id ,
				);
			}
			else {
				return array(
					"success" 			=> true ,
					"paid" 				=> $charge->paid ,
					"status" 			=> $charge->status ,
					"captured" 			=> $charge->captured ,
					"transaction_id" 	=> $charge->id ,
					"status" 			=> $charge->status ,
				);
			}
		}
		catch(Stripe\Error\InvalidRequest $ex){
			$body = $ex->getJsonBody();
			$error = $body['error'] ;
			// Log::info(__FUNCTION__.":error". __LINE__);
			// Log::info(print_r($error,1));
			return array(
				"success" 			=> false ,
				"type" 				=> $error["type"] ,
				"code" 				=> '' ,
				"message" 			=> $error["message"] ,
				"transaction_id" 	=> -1
			);
		}
	}

	public static function chargeByCustomerId($customerId,$amount, $description, $currency = null, $capture = true){
		self::setApiKey();
		// fix amount for payment
		$amount = round($amount*100) ;

		try {
			$charge = \Stripe\Charge::create(
				array(
					"amount" 		=> $amount,
					"currency" 		=> $currency ? $currency : Settings::getCurrency(),
					"customer" 		=> $customerId,
					"description" 	=> $description,
					"capture"       => $capture
					)
				);

			// Log::info(__FUNCTION__.":charge". __LINE__);
			// Log::info(print_r($charge,1));

			if($charge->failure_code){
				return array(
					"success" 			=> false ,
					"type" 				=> 'card_error' ,
					"code" 				=> $charge->failure_code ,
					"message" 			=> trans("paymentError.".$charge->failure_code),
					"transaction_id" 	=> $charge->id ,
				);
			}
			else {
				return array(
					"success" 			=> true ,
					"paid" 				=> $charge->paid ,
					"status" 			=> $charge->status ,
					"captured" 			=> $charge->captured ,
					"transaction_id" 	=> $charge->id ,
					"status" 			=> $charge->status ,
				);
			}
		}
		catch(Stripe\Error\InvalidRequest $ex){
			$body = $ex->getJsonBody();
			$error = $body['error'] ;
			//Log::info(__FUNCTION__.":error". __LINE__);
			//Log::info(print_r($error,1));
			return array(
				"success" 			=> false ,
				"type" 				=> $error["type"] ,
				"code" 				=> '' ,
				"message" 			=> $error["message"] ,
				"transaction_id" 	=> -1
			);
		}
	}

	public static function uploadIdentityDocument($filePath = null, $accountId = null){

		self::setApiKey();

		$return = [];

		 try{
			$uploadData = array(
					"purpose" 	=> "identity_document",
					"file" 		=> fopen($filePath, 'r')
				);
				array("stripe_account" => $accountId);
		

			$file = null;
			
			if(!$file) {
				$file = \Stripe\FileUpload::create($uploadData);
			}
			else{
				$file->save($uploadData);
			}

			$return['file_token'] = $file->id;
			
			//Log::info("acc".$providerDocument->file_token);
			// Log::info("file:".print_r($file->id,1));
		
			//return $return;
			
			return array(
					"success" 					=> true ,
					"file_token" 				=> $return['file_token'],
			);
			
		}		
		catch(Stripe\Error\InvalidRequest $ex){
			$body = $ex->getJsonBody();
			$error = $body['error'] ;
			return array(
				"success" 					=> false,
				"file_token" 				=> 'empty',
				"type" 						=> $error["type"] ,
				"message" 					=> $error["message"] ,
				
				
			);
		}
	}

	public static function createOrUpdateAccount($ledgerBankAccount){

		self::setApiKey();
		
		$return = [];

		$ledger = $ledgerBankAccount->ledger ;

		$provider = $ledger->provider ;

		/**
		 * Set default how many days provider will receive cash after service is completed.
		 */
		$time = 7;
		$settingTransferInterval = Settings::where('key', 'provider_transfer_interval')->first();
		if($settingTransferInterval->value == 'daily')
			$time = 1;
		else if($settingTransferInterval->value == 'weekly')
			$time = 7;
		else if($settingTransferInterval->value == 'monthly')
			$time = 30;

		$providerDocument = $provider->getDocumentIdentity();
		$data = explode("/",  $ledgerBankAccount->birthday_date);

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
				'dob'                => array('year' => $data[0], 'month' => $data[1], 'day' => $data[2]),
				
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

		if($providerDocument){
			$accountData['legal_entity'] ['verification'] = array(
				'document' => $providerDocument->file_token
			);
		}

		$account = null;

		/**
		 * Se recipient_id não iniciar com acct_ (padrão do Stripe) não manda recupear as informações
		 * do recipient_id e cria uma nova conta bancária.
		 */
		if(($ledgerBankAccount->recipient_id) && (strpos($ledgerBankAccount->recipient_id, "acct_") === 0)){
			$account = \Stripe\Account::retrieve($ledgerBankAccount->recipient_id);
		}

		if(!$account) {
			$dataStripe = $account = \Stripe\Account::create($accountData);
		}
		else{
			$account->external_account = array(
					'object' => 'bank_account',
					'country' => 'BR',
					'currency' => 'brl',
					'routing_number' => $ledgerBankAccount->getRoutingNumber(),
					'account_number' => $ledgerBankAccount->getAccountNumber()
				);
			
			$account->legal_entity->type 							= $ledgerBankAccount->person_type;

			if($account->legal_entity->verification->status != 'verified')
				$account->legal_entity->personal_id_number 				= preg_replace('/[^0-9]/', '', $ledgerBankAccount->document);
			
			$account->legal_entity->dob 							= array('year' => $data[0], 'month' => $data[1], 'day' => $data[2]);
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
			if($providerDocument && ($account->legal_entity->verification->document != $providerDocument->file_token)){
				$account->legal_entity->verification->document 	= $providerDocument->file_token;
			}
			
			// if($time >= $account->payout_schedule->delay_days){
			// 	$account->payout_schedule->delay_days = $time;
			// 	$account->payout_schedule->interval = "daily";
			// }

			$account->tos_acceptance = array('date' => time(), 'ip' => '127.0.0.1');
			
			try{
				$dataStripe = $account->save();
			} catch(Stripe\Error\InvalidRequest $ex){
				\Log::error($ex);
				$body = $ex->getJsonBody();
				$error = trans('setting.bank_stripe_error');

				if(isset($body['error']['message'])){
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

		if($account->id){
			$return = array(
				"success" 					=> true ,
				"recipient_id"				=> $account->id,
				"message_error"				=> null);
		}
		else {
			$return = array(
				"success" 					=> false ,
				"recipient_id"				=> 'empty',
				"message_error"				=> trans('setting.bank_stripe_error'));
		}
		
		return $return;
	}

	public static function retrieveCharge($chargeTransaction){
		self::setApiKey();

		return \Stripe\Charge::retrieve($chargeTransaction);
	}

	/**
	 * Capture previous Transaction.
	 * 
	 * @param {Request} $request
	 * @param {Array}	$stripeCharge
	 */
	public static function capturePreviousAuthorizedCharge(Requests $request, $stripeCharge){
		self::setApiKey();

		$requestCharge = $request->getCharge();

		$stripeCharge->amount = $requestCharge['total'];

		$amount = round(($requestCharge['total'] * 100), 0);

		$response = $stripeCharge->capture(array("amount" => $amount));

		return $response;
	}

	/**
	 * Capture previous Transaction with a destination for split.
	 * 
	 * @param {Request} $request
	 * @param {Array}	$stripeCharge
	 */
	public static function capturePreviousAuthorizedChargeWithSplit(Requests $request, $stripeCharge){
		self::setApiKey();

		$requestCharge = $request->getCharge();
		$providerBankAccount = $request->getProviderBankAccount();

		/**
		 * Check if Destination Bank Account is equal from Approved Provider Bank Account, 
		 * if it is not do a refund and create a new charge and if it is do a capture
		 */
		if($stripeCharge->destination == $providerBankAccount){
			$amount = round(($requestCharge['total'] * 100), 0);
			$amountProvider = round(($requestCharge['provider_value'] * 100), 0);
			
			$stripeCharge->destination = $providerBankAccount;
			$stripeCharge->amount = $requestCharge['total'];
	
			$response = $stripeCharge->capture(
				array(
					"amount" => $amount,
					"destination" 	=> array(
						"amount" 	=> $amountProvider
					)
				)
			);

			return $response;
		} else {
			$refundGatewayResponse = RequestCharging::refundRequest($request);
			$gatewayResponse = self::chargeRequest($request, false);

			return $gatewayResponse;
		}
	}

	public static function capturePreviousAuthorizedAndTryNewCharge(Requests $request, $chargeRequest){
		self::setApiKey();

		// Tenta capturar o valor da captura resalizada antes
		$transaction = $request->getTransactionRequestPrice();
		$charge = self::retrieveCharge($transaction->gateway_transaction_id);

		$chargeResponse = $charge->capture();

		$remainingValue = $chargeRequest['total']-(($charge->amount)/100);

		$payment = $request->getFirstOrDefaultPayment();


		if ($chargeResponse['status'] == "succeeded" && $chargeResponse["captured"] == true){

			// Captura realizada feita com sucesso

			$transaction->type 				= Transaction::REQUEST_PRICE;
			$transaction->status 			= Transaction::MapStatus[$chargeResponse['status']];
			$transaction->gross_value 	 	= $chargeRequest['total'];
			$transaction->provider_value 	= 0;
			$transaction->gateway_tax_value = ($chargeRequest['total'] * $chargeRequest['payment_tax']) + $chargeRequest['payment_fee'];
			$transaction->net_value 		= $chargeRequest['total'] - $transaction->gateway_tax_value ;
			$transaction->save();

			$providerBankAccount = $request->getProviderBankAccount();
			$chargeRequestFull = $request->getCharge();

			if((Settings::getStripeConnect() == self::CUSTOM_ACCOUNTS) && $providerBankAccount != 'empty'){
				$stripeResponse = self::chargeByCustomerIdWithSplit($payment->customer_id, $remainingValue, $chargeRequestFull['provider_value'], $providerBankAccount,  sprintf(trans('payment.ride_payment'), $request->id));

				if($stripeResponse["success"]){

					// Segunda captura realizada com sucesso

					$paymentTax = RequestCharging::getGatewayTax() ;
					$paymentFee = RequestCharging::getGatewayFee() ;

					$newTransaction 					= new Transaction();

					$newTransaction->type 				= Transaction::REQUEST_PRICE;
					$newTransaction->status 			= Transaction::MapStatus[$stripeResponse['status']];
					$newTransaction->gross_value 	 	= $remainingValue;
					$newTransaction->provider_value 	= $chargeRequestFull['provider_value'];
					$newTransaction->gateway_tax_value 	= ($remainingValue * $paymentTax) + $paymentFee;
					$newTransaction->net_value 			= $remainingValue - $transaction->gateway_tax_value - $chargeRequestFull['provider_value'];
					$newTransaction->gateway_transaction_id = $stripeResponse["transaction_id"];

					$newTransaction->save();

					$request->request_price_transaction_id = $newTransaction->id;
					$request->card_payment = $chargeRequest['total'];
					$request->is_base_fee_paid = 1;
					$request->is_paid = 1;

					$request->save();
					// gera credito a compensar do motorista
					if($request->currentProvider && $request->currentProvider->Ledger){
						Finance::createFinanceSplitInformation($request->currentProvider->Ledger->id, $chargeRequest['provider_value'],
							$request->id, self::getNextCompensationDate(), trans('finance.ride_card_payment'), trans('finance.ride_card_payment'),
							Finance::RIDE_CREDIT, Finance::RIDE_DEBIT);
					}

					return $stripeResponse;
				}else {
					$stripeResponse = self::capturePreviousAuthorizedAndTryNewChargeNoSplit($request, $chargeRequest, $transaction);
					return $stripeResponse;
				}
			}
			else {
				$stripeResponse = self::capturePreviousAuthorizedAndTryNewChargeNoSplit($request, $chargeRequest, $transaction);
				return $stripeResponse;
			}
		}else {
			$amountRound = ($charge->amount)/100;

			$transaction 					= new Transaction();
			$transaction->type 				= Transaction::REQUEST_PRICE;
			$transaction->status 			= Transaction::ERROR;
			$transaction->gross_value 	 	= $amountRound;
			$transaction->provider_value 	= 0;
			$transaction->gateway_tax_value = $amountRound * $chargeRequest['payment_tax'] + $chargeRequest['payment_fee'];
			$transaction->net_value 		= $amountRound - $transaction->gateway_tax_value ;
			//$transaction->payment_gateway_error 	= $chargeResponse['message'] ;
		//	$transaction->gateway_transaction_id 	= $chargeResponse['id'];

			$transaction->save();

			$request->request_price_transaction_id = $transaction->id;
			$request->card_payment 		= $request->total;
			$request->is_base_fee_paid 	= 0;
			$request->is_paid 			= 0;

			$request->save();

			// gera o debito do usuario
			if($request->User && $request->User->Ledger)
				Finance::createRidePaymentFailDebit($request->User->Ledger->id, $request->total, $request->id);

			return $chargeResponse;
		}
	}

	public static function capturePreviousAuthorizedAndTryNewChargeNoSplit(Requests $request, $chargeRequest, Transaction $transaction){
		self::setApiKey();

		// Tenta capturar o valor da captura resalizada antes
		$charge = self::retrieveCharge($transaction->gateway_transaction_id);

		$remainingValue = $chargeRequest['total']-(($charge->amount)/100);

		$payment = $request->getFirstOrDefaultPayment();

		// Captura realizada feita com sucesso

		$providerBankAccount = $request->getProviderBankAccount();
		$chargeRequestFull = $request->getCharge();
		
		$stripeResponse = self::chargeByCustomerId($payment->customer_id, $remainingValue, sprintf(trans('payment.ride_payment'), $request->id));

		if($stripeResponse["success"]){

			// Segunda captura realizada com sucesso

			$paymentTax = RequestCharging::getGatewayTax() ;
			$paymentFee = RequestCharging::getGatewayFee() ;

			$newTransaction 					= new Transaction();

			$newTransaction->type 				= Transaction::REQUEST_PRICE;
			$newTransaction->status 			= Transaction::MapStatus[$stripeResponse['status']];
			$newTransaction->gross_value 	 	= $remainingValue;
			$newTransaction->provider_value 	= 0;
			$newTransaction->gateway_tax_value 	= ($remainingValue * $paymentTax) + $paymentFee;
			$newTransaction->net_value 			= $remainingValue - $transaction->gateway_tax_value;
			$newTransaction->gateway_transaction_id = $stripeResponse["transaction_id"];

			$newTransaction->save();

			$request->request_price_transaction_id = $newTransaction->id;
			$request->card_payment = $chargeRequest['total'];
			$request->is_base_fee_paid = 1;
			$request->is_paid = 1;

			$request->save();
			// gera credito a compensar do motorista
			if($request->currentProvider && $request->currentProvider->Ledger)
				Finance::createRideCardPayment($request->currentProvider->Ledger->id, $chargeRequest['provider_value'], $request->id, self::getNextCompensationDate());

			return $stripeResponse;
		}else {
			// Segunda captura falhou

			$paymentTax = RequestCharging::getGatewayTax() ;
			$paymentFee = RequestCharging::getGatewayFee() ;

			$newTransaction 					= new Transaction();
			$newTransaction->type 				= Transaction::REQUEST_PRICE;
			$newTransaction->status 			= Transaction::ERROR;
			$newTransaction->gross_value 	 	= $remainingValue;
			$newTransaction->provider_value 	= 0;
			$newTransaction->gateway_tax_value = ($remainingValue * $paymentTax) + $paymentFee;
			$newTransaction->net_value 		= $remainingValue - $transaction->gateway_tax_value;
		//	$transaction->payment_gateway_error 	= $stripeResponse['message'] ;
			$transaction->gateway_transaction_id 	= $stripeResponse["transaction_id"];

			$newTransaction->save();

			$request->request_price_transaction_id = $newTransaction->id;
			$request->card_payment 		= $request->total;
			$request->is_base_fee_paid 	= 0;
			$request->is_paid 			= 0;

			$request->save();

			$stripeResponse['second_payment_failed'] = true;

			return $stripeResponse;
		}
	}

	/**
	 * Cria um charge mas não captura o valor (deve ser utilizado ao criar a request)
	 * @param Requests $request
	 * @param $estimatedValue
	 * @return array
	 */
	 /*
	public static function chargeRequestNoCapture(Requests $request, $estimatedValue){
		self::setApiKey();

		$payment = $request->getFirstOrDefaultPayment();

		if (!$payment)
			return self::notFoundCardResponse();

		$stripeResponse = self::chargeByCustomerId($payment->customer_id, $estimatedValue, sprintf(trans('payment.ride_authorization'), $request->id), null, false);

		if ($stripeResponse["success"] == true && $stripeResponse["captured"] == false) {
			$transaction = self::saveTransaction(Transaction::REQUEST_PRICE, Transaction::AUTHORIZED, $estimatedValue, 0, 0, 0, $stripeResponse['transaction_id']);

			$request->request_price_transaction_id = $transaction->id;
			$request->save();

			return $stripeResponse;
		} else {
			$transaction = self::saveTransaction(Transaction::REQUEST_PRICE, Transaction::ERROR, $estimatedValue, 0, 0, 0, $stripeResponse['transaction_id']);

			$request->request_price_transaction_id = $transaction->id;
			$request->card_payment = $estimatedValue;
			$request->is_base_fee_paid = 0;
			$request->is_paid = 0;

			$request->save();

			return $stripeResponse;
		}
	}*/

	public static function chargeNoCapture($userId, $estimatedValue, $estimatedValueProvider, $provider_id){
		self::setApiKey();

		$payment = Payment::getFirstOrDefaultPayment($userId);

		if (!$payment)
			return self::notFoundCardResponse();
			
		if((Settings::getStripeConnect() == self::CUSTOM_ACCOUNTS)){
			/**
			 * Because of Split Refund, is sent the current provider account to check if user has funds.
			 */
			$bank_account = LedgerBankAccount::where("provider_id", "=", $provider_id)->first();
			$stripeResponse = self::chargeByCustomerIdWithSplit($payment->customer_id, $estimatedValue, $estimatedValueProvider, $bank_account->recipient_id, sprintf(trans('payment.ride_authorization')), null, false);
		}
		else 
			$stripeResponse = self::chargeByCustomerId($payment->customer_id, $estimatedValue, sprintf(trans('payment.ride_authorization')), null, false);

		if ($stripeResponse["success"] == true && $stripeResponse["captured"] == false) {
			return self::saveTransaction(Transaction::REQUEST_PRICE, Transaction::AUTHORIZED, $estimatedValue, 0, 0, 0, $stripeResponse['transaction_id']);
		}

		return self::saveTransaction(Transaction::REQUEST_PRICE, Transaction::ERROR, $estimatedValue, 0, 0, 0, $stripeResponse['transaction_id']);
	}

	public static function chargeRequest(Requests $request, $createFinance = true){
		$payment = $request->getFirstOrDefaultPayment();

		if($payment){
			$charge = $request->getCharge();

			$providerBankAccount = $request->getProviderBankAccount();

			if((Settings::getStripeConnect() == self::CUSTOM_ACCOUNTS) && $providerBankAccount != 'empty'){
				$return = self::chargeByCustomerIdWithSplit($payment->customer_id, $charge['total'], $charge['provider_value'], $providerBankAccount, sprintf(trans('payment.ride_payment'), $request->id));
			
				if($return["success"] && $return["paid"]){
					$transaction 					= new Transaction();
					$transaction->type 				= Transaction::REQUEST_PRICE;
					$transaction->status 			= 'paid';
					$transaction->gross_value 	 	= $request->total;
					$transaction->provider_value 	= $charge['provider_value'];
					$transaction->gateway_tax_value = ($request->total * $charge['payment_tax']) + $charge['payment_fee'];
					$transaction->net_value 		= $request->total - $transaction->gateway_tax_value - $charge['provider_value'];
					$transaction->gateway_transaction_id = $return["transaction_id"];
	
					$transaction->save();
	
					$request->request_price_transaction_id = $transaction->id;
					$request->card_payment = $request->total;
					$request->is_base_fee_paid = 1;
					$request->is_paid = 1;
					$request->provider_commission = $charge['provider_value'] ;
					$request->gross_value = $charge['company_value'];
	
					$request->save();

					// Cria compensação cria crédito e débito
					if(($request->currentProvider) && ($request->currentProvider->Ledger) && ($createFinance == true)){
						Finance::createFinanceSplitInformation($request->currentProvider->Ledger->id, $charge['provider_value'],
							$request->id, self::getNextCompensationDate(), trans('finance.ride_card_payment'), trans('finance.ride_card_payment'),
							Finance::RIDE_CREDIT, Finance::RIDE_DEBIT);
					}
	
					return $return ;
				}
				else {
					$return = self::chargeRequestNoSplit($request);
				}
			} else {
				$return = self::chargeRequestNoSplit($request);
			}

			return $return;
		}
		else {
			return array(
				"success" 	=> false ,
				"type" 		=> 'card_not_found' ,
				"code" 		=> 'card_not_found' ,
				"message" 	=> trans('paymentError.card_not_found'),
			);
		}
	}

	public static function chargeRequestNoSplit(Requests $request){
		
		$payment = $request->getFirstOrDefaultPayment();

		if($payment){
			$charge = $request->getCharge();

			//Split falhou faz cobrança normal.
			$return = self::chargeByCustomerId($payment->customer_id, $charge['total'], sprintf(trans('payment.ride_payment'), $request->id));

			// foi pago
			if($return["success"] && $return["paid"]){
				$transaction 					= new Transaction();
				$transaction->type 				= Transaction::REQUEST_PRICE;
				$transaction->status 			= 'paid';
				$transaction->gross_value 	 	= $request->total;
				$transaction->provider_value 	= 0;
				$transaction->gateway_tax_value = ($request->total * $charge['payment_tax']) + $charge['payment_fee'];
				$transaction->net_value 		= $request->total - $transaction->gateway_tax_value ;
				$transaction->gateway_transaction_id = $return["transaction_id"];

				$transaction->save();

				$request->request_price_transaction_id = $transaction->id;
				$request->card_payment = $request->total;
				$request->is_base_fee_paid = 1;
				$request->is_paid = 1;
				$request->provider_commission = $charge['provider_value'] ;
				$request->gross_value = $charge['company_value'];

				$request->save();
				// gera credito a compensar do motorista
				if($request->currentProvider && $request->currentProvider->Ledger)
					Finance::createRideCardPayment($request->currentProvider->Ledger->id, $charge['provider_value'], $request->id, self::getNextCompensationDate());

				return $return ;
			}
			else {
				$transaction 					= new Transaction();
				$transaction->type 				= Transaction::REQUEST_PRICE;
				$transaction->status 			= Transaction::ERROR;
				$transaction->gross_value 	 	= $request->total;
				$transaction->provider_value 	= 0;
				$transaction->gateway_tax_value = $request->total * $charge['payment_tax'] + $charge['payment_fee'];
				$transaction->net_value 		= $request->total - $transaction->gateway_tax_value ;
				$transaction->payment_gateway_error 	= $return['message'] ;
				$transaction->gateway_transaction_id 	= $return["transaction_id"];

				$transaction->save();

				$request->request_price_transaction_id = $transaction->id;
				$request->card_payment 		= $request->total;
				$request->is_base_fee_paid 	= 0;
				$request->is_paid 			= 0;

				$request->save();

				// gera o debito do usuario
				if($request->User && $request->User->Ledger)
					Finance::createRidePaymentFailDebit($request->User->Ledger->id, $request->total, $request->id);
				
				// gera credito a compensar do motorista
				if($request->currentProvider && $request->currentProvider->Ledger)
					Finance::createRideCardPayment($request->currentProvider->Ledger->id, $charge['provider_value'], $request->id, self::getNextCompensationDate());

				return $return ;
			}
		}
	}
	
	public static function refundRequest(Requests $request){
		self::setApiKey();
		
		$transaction = $request->getTransactionRequestPrice();
		if($transaction && $transaction->status != Transaction::REFUNDED){

			$refund = \Stripe\Refund::create(
				array(
					"charge" => $transaction->gateway_transaction_id
					)
				);

			// Log::info(__FUNCTION__.":refund". __LINE__);
			// Log::info(print_r($refund,1));

			if($refund->failure_code){

				$transaction->status = Transaction::PENDING_REFUND;
				$transaction->save();

				return array(
					"success" 			=> false ,
					"type" 				=> 'refund_error' ,
					"code" 				=> $refund->failure_code ,
					"message" 			=> trans("paymentError.".$refund->failure_code),
					"transaction_id" 	=> $refund->id ,
				);
			}
			else {

				$transaction->status = Transaction::REFUNDED;
				$transaction->save();

				return array(
					"success" 			=> true ,
					"status" 			=> $refund->status ,
					"transaction_id" 	=> $refund->id ,
				);
			}
		}
		else {
			return array(
				"success" 			=> false ,
				"type" 				=> 'refund_error' ,
				"code" 				=> 1 ,
				"message" 			=> trans("paymentError.noTrasactionRefundFound"),
				"transaction_id" 	=> null ,
			);
		}
	}

	/**
	 * Do refund recovering value sent to Provider
	 */
	public static function refundRequestWithSplit(Requests $request){
		self::setApiKey();
		
		$transaction = $request->getTransactionRequestPrice();
		if($transaction && $transaction->status != Transaction::REFUNDED){
			$refund = \Stripe\Refund::create(
				array(
					"charge" => $transaction->gateway_transaction_id,
  					"reverse_transfer" => true
				)
			);

			// Log::info(__FUNCTION__.":refund". __LINE__);
			// Log::info(print_r($refund,1));

			if($refund->failure_code){

				$transaction->status = Transaction::PENDING_REFUND;
				$transaction->save();

				return array(
					"success" 			=> false ,
					"type" 				=> 'refund_error' ,
					"code" 				=> $refund->failure_code ,
					"message" 			=> trans("paymentError.".$refund->failure_code),
					"transaction_id" 	=> $refund->id ,
				);
			}
			else {

				$transaction->status = Transaction::REFUNDED;
				$transaction->save();

				return array(
					"success" 			=> true ,
					"status" 			=> $refund->status ,
					"transaction_id" 	=> $refund->id ,
				);
			}
		}
		else {
			return array(
				"success" 			=> false ,
				"type" 				=> 'refund_error' ,
				"code" 				=> 1 ,
				"message" 			=> trans("paymentError.noTrasactionRefundFound"),
				"transaction_id" 	=> null ,
			);
		}
	}

	public static function chargeCancellationFee(Requests $request, $cancelPrice, $providerValue){
		$payment = $request->getFirstOrDefaultPayment();

		if($payment){
			$paymentTax = RequestCharging::getGatewayTax();
			$paymentFee = RequestCharging::getGatewayFee();
			$providerBankAccount = $request->getProviderBankAccount();

			if((Settings::getStripeConnect() == self::CUSTOM_ACCOUNTS) && $providerBankAccount != 'empty'){
				$return = self::chargeByCustomerIdWithSplit($payment->customer_id, $cancelPrice, $providerValue, $providerBankAccount, sprintf(trans('payment.cancel_tax'), $request->id));
				
				if($return["success"] && $return["paid"]){
					$transaction 							= new Transaction();
					$transaction->type 						= Transaction::CANCEL_TAX;
					$transaction->status 					= Transaction::MapStatus[$return['status']];
					$transaction->gross_value 	 			= $cancelPrice;
					$transaction->provider_value 			= $providerValue;
					$transaction->gateway_tax_value 		= ($cancelPrice * $paymentTax) + $paymentFee;
					$transaction->net_value 				= $cancelPrice - $transaction->gateway_tax_value - $providerValue;
					$transaction->gateway_transaction_id 	= $return["transaction_id"];
	
					$transaction->save();
	
					$request->request_price_transaction_id = $transaction->id;
					$request->card_payment = $cancelPrice;
					$request->total = $cancelPrice;
					$request->provider_commission = $providerValue;
					$request->is_cancel_fee_paid = 1;
					$request->save();

					$requestService = \RequestServices::where('request_id', '=', $request->id)->first();
					$requestService->cancel_price = $cancelPrice;
					$requestService->total = $cancelPrice;
					$requestService->save();

					if($request->User && $request->User->Ledger){
						Finance::createRideCancellationDebit($request->User->Ledger->id, $cancelPrice, $request->id);
					}
					
					// Cria compensação cria crédito e débito
					if($request->currentProvider && $request->currentProvider->Ledger){
						Finance::createFinanceSplitInformation($request->currentProvider->Ledger->id, $providerValue,
							$request->id, self::getNextCompensationDate(), trans('finance.ride_cancellation_fee'), trans('finance.ride_cancellation_fee'),
							Finance::RIDE_CANCELLATION_CREDIT, Finance::RIDE_CANCELLATION_DEBIT);
					}

					return $return ;
				}
				else {
					$return = self::chargeCancellationFeeNoSplit($request, $cancelPrice, $providerValue);
				}
			}
			else {
				$return = self::chargeCancellationFeeNoSplit($request, $cancelPrice, $providerValue);
			}

			return $return;
		}
		else {
			return array(
				"success" 	=> false ,
				"type" 		=> 'card_not_found' ,
				"code" 		=> 'card_not_found' ,
				"message" 	=> trans('paymentError.card_not_found'),
			);
		}
	}

	public static function chargeCancellationFeeNoSplit (Requests $request, $cancelPrice, $providerValue){
		$payment = $request->getFirstOrDefaultPayment();

		if($payment){
			$paymentTax = RequestCharging::getGatewayTax();
			$paymentFee = RequestCharging::getGatewayFee();
			$providerBankAccount = $request->getProviderBankAccount();

			$return = self::chargeByCustomerId($payment->customer_id, $cancelPrice, sprintf(trans('payment.cancel_tax'), $request->id));
			// foi pago
			if($return["success"] && $return["paid"]){
				$transaction 							= new Transaction();
				$transaction->type 						= Transaction::CANCEL_TAX;
				$transaction->status 					= Transaction::MapStatus[$return['status']];
				$transaction->gross_value 	 			= $cancelPrice;
				$transaction->provider_value 			= 0;
				$transaction->gateway_tax_value 		= ($cancelPrice * $paymentTax) + $paymentFee;
				$transaction->net_value 				= $cancelPrice - $transaction->gateway_tax_value;
				$transaction->gateway_transaction_id 	= $return["transaction_id"];

				$transaction->save();

				$request->request_price_transaction_id = $transaction->id;
				$request->card_payment = $cancelPrice;
				$request->total = $cancelPrice;
				$request->provider_commission = $providerValue;
				$request->is_cancel_fee_paid = 1;
				$request->save();

				$requestService = \RequestServices::where('request_id', '=', $request->id)->first();
				$requestService->cancel_price = $cancelPrice;
				$requestService->total = $cancelPrice;
				$requestService->save();

				if($request->User && $request->User->Ledger){
					Finance::createRideCancellationDebit($request->User->Ledger->id, $cancelPrice, $request->id);
				}
				
				// gera credito a compensar do motorista
				if($request->currentProvider && $request->currentProvider->Ledger){
					Finance::createRideCancellationCredit($request->currentProvider->Ledger->id, $providerValue, $request->id, self::getNextCompensationDate());
				}
				
				return $return;
			}
			else {
				$transaction 					= new Transaction();
				$transaction->type 				= Transaction::REQUEST_PRICE;
				$transaction->status 			= Transaction::ERROR;
				$transaction->gross_value 	 	= $cancelPrice;
				$transaction->provider_value 	= 0;
				$transaction->gateway_tax_value = ($cancelPrice * $paymentTax) + $paymentFee;
				$transaction->net_value 		= $cancelPrice - $transaction->gateway_tax_value;
				$transaction->payment_gateway_error 	= $return['message'] ;
				$transaction->gateway_transaction_id 	= $return["transaction_id"];

				$transaction->save();

				/**
				 * Colocar que pagamento não foi realizado
				 * criar dívida pro usuário
				 * criar crédito a compensar para o motorista.
				 */

				$request->is_cancel_fee_paid = 0;
				$request->ledger_payment = $cancelPrice;
				$request->provider_commission = $providerValue;
				$request->save();

				if($request->User && $request->User->Ledger){
					Finance::createRideCancellationDebit($request->User->Ledger->id, $cancelPrice, $request->id);
				}

				if($request->currentProvider && $request->currentProvider->Ledger && $providerValue > 0){
					Finance::createRideCancellationCredit($request->currentProvider->Ledger->id, $providerValue, $request->id, self::getNextCompensationDate());
				}

				return $return;
			}
		}
	}

	public static function chargePendingRequest(Requests $request, Payment $payment){

		if($payment){
			$charge = $request->getCharge();

			
			$providerBankAccount = $request->getProviderBankAccount();

			if((Settings::getStripeConnect() == self::CUSTOM_ACCOUNTS) && $providerBankAccount != 'empty'){
				$return = self::chargeByCustomerIdWithSplit($payment->customer_id, $charge['total'], $charge['provider_value'], $providerBankAccount,  sprintf(trans('payment.ride_payment'), $request->id));
				
				if($return["success"] && $return["paid"]){
					$transaction 					= new Transaction();
					$transaction->type 				= Transaction::REQUEST_PRICE;
					$transaction->status 			= Transaction::MapStatus[$return['status']];
					$transaction->gross_value 	 	= $request->total;
					$transaction->provider_value 	= 0;
					$transaction->gateway_tax_value = ($request->total * $charge['payment_tax']) + $charge['payment_fee'];
					$transaction->net_value 		= $request->total - $transaction->gateway_tax_value ;
					$transaction->gateway_transaction_id = $return["transaction_id"];
	
					$transaction->save();
	
					$request->request_price_transaction_id = $transaction->id;
					$request->card_payment = $request->total;;
					$request->is_base_fee_paid = 1;
					$request->is_paid = 1;
	
					$request->save();
	
					if($request->User && $request->User->Ledger){
						// comprovante de pagamento da pendencia
						Finance::createRideCardPaymentPending($request->User->Ledger->id, $request->total, $request->id);
					}
					if($request->currentProvider && $request->currentProvider->Ledger){
						Finance::createFinanceSplitInformation($request->currentProvider->Ledger->id, $providerValue,
							$request->id, self::getNextCompensationDate(), trans('finance.ride_card_payment'), trans('finance.ride_card_payment'),
							Finance::RIDE_CREDIT, Finance::RIDE_DEBIT);
					}
					return $return ;
				}
				else {
					$return = self::chargePendingRequestNoSplit($request, $payment);
				}
			} else {
				$return = self::chargePendingRequestNoSplit($request, $payment);
			}
			
			return $return ;
		}
		else {
			return array(
				"success" 	=> false ,
				"type" 		=> 'card_not_found' ,
				"code" 		=> 'card_not_found' ,
				"message" 	=> trans('paymentError.card_not_found'),
			);
		}
	}

	public static function chargePendingRequestNoSplit(Requests $request, Payment $payment){

		if($payment){
			$charge = $request->getCharge();

			$providerBankAccount = $request->getProviderBankAccount();
			
			$return = self::chargeByCustomerId($payment->customer_id, $charge['total'], sprintf(trans('payment.ride_payment'), $request->id));

			// foi pago
			if($return["success"] && $return["paid"]){
				$transaction 					= new Transaction();
				$transaction->type 				= Transaction::REQUEST_PRICE;
				$transaction->status 			= Transaction::MapStatus[$return['status']];
				$transaction->gross_value 	 	= $request->total;
				$transaction->provider_value 	= 0;
				$transaction->gateway_tax_value = ($request->total * $charge['payment_tax']) + $charge['payment_fee'];
				$transaction->net_value 		= $request->total - $transaction->gateway_tax_value ;
				$transaction->gateway_transaction_id = $return["transaction_id"];

				$transaction->save();

				$request->request_price_transaction_id = $transaction->id;
				$request->card_payment = $request->total;;
				$request->is_base_fee_paid = 1;
				$request->is_paid = 1;

				$request->save();

				if($request->currentProvider && $request->currentProvider->Ledger){
					// comprovante de pagamento da pendencia
					Finance::createRideCardPaymentPending($request->User->Ledger->id, $request->total, $request->id);

					// gera credito a compensar do motorista
					Finance::createRideCardPayment($request->currentProvider->Ledger->id, $charge['provider_value'], $request->id, self::getNextCompensationDate());
				}
				return $return ;
			}
			else {
				$transaction 					= new Transaction();
				$transaction->type 				= Transaction::REQUEST_PRICE;
				$transaction->status 			= Transaction::ERROR;
				$transaction->gross_value 	 	= $request->total;
				$transaction->provider_value 	= 0;
				$transaction->gateway_tax_value = $request->total * $charge['payment_tax'] + $charge['payment_fee'];
				$transaction->net_value 		= $request->total - $transaction->gateway_tax_value ;
				$transaction->payment_gateway_error 	= $return['message'] ;
				$transaction->gateway_transaction_id 	= $return["transaction_id"];

				$transaction->save();

				$request->request_price_transaction_id = $transaction->id;
				$request->card_payment 		= $request->total;
				$request->is_base_fee_paid 	= 0;
				$request->is_paid 			= 0;

				$request->save();

				return $return ;
			}
		}
	}

	public static function createCardTransactionUpdateRequest($stripeResponse, $charge, $request){
		if ($stripeResponse["status"] == 'succeeded' && $stripeResponse["paid"]){
			$transaction = $request->getTransactionRequestPrice();

			$transaction->updateTransactionPaymentCard(Transaction::REQUEST_PRICE, Transaction::PAID, $charge);

			$request->request_price_transaction_id = $transaction->id;
			$request->card_payment = $charge['total'];
			$request->is_base_fee_paid = 1;
			$request->is_paid = 1;
			$request->provider_commission =  $charge['provider_value'];

			$request->save();

			
			$providerBankAccount = $request->getProviderBankAccount();

			if((Settings::getStripeConnect() == self::CUSTOM_ACCOUNTS) && $providerBankAccount != 'empty'){
				// gera credito a compensar do motorista
				if($request->currentProvider && $request->currentProvider->Ledger){
					Finance::createFinanceSplitInformation($request->currentProvider->Ledger->id, $charge['provider_value'],
						$request->id, self::getNextCompensationDate(), trans('finance.ride_card_payment'), trans('finance.ride_card_payment'),
						Finance::RIDE_CREDIT, Finance::RIDE_DEBIT);
				}
			} else {
				// gera credito a compensar do motorista
				if($request->currentProvider && $request->currentProvider->Ledger)
					Finance::createRideCardPayment($request->currentProvider->Ledger->id, $charge['provider_value'], $request->id, self::getNextCompensationDate());
			}

			
		}else{
			Transaction::createTransactionPaymentCard(Transaction::REQUEST_PRICE, Transaction::ERROR, $charge, $stripeResponse['transaction_id'], $stripeResponse['message']);

			throw new Exception($stripeResponse['message']);
		}

		return $transaction;
	}


	public function getNextCompensationDate(){
		$carbon = Carbon::now();
		$compDays = Settings::findByKey('compensate_provider_days');
		$addDays = ($compDays || (string)$compDays == '0') ? (int)$compDays : 31;
		$carbon->addDays($addDays);
		
		return $carbon;
	}

	private static function saveTransaction($type, $status, $grossValue, $providerValue, $gatewayTaxValue, $netValue, $gatewayTransactionId){
		$transaction = new Transaction();

		$transaction->type = $type;
		$transaction->status = $status;
		$transaction->gross_value = $grossValue;
		$transaction->provider_value = $providerValue;
		$transaction->gateway_tax_value = $gatewayTaxValue;
		$transaction->net_value = $netValue;
		$transaction->gateway_transaction_id = $gatewayTransactionId;

		$transaction->save();

		return $transaction;
	}

	private static function notFoundCardResponse(){
		return array(
			"success" 	=> false ,
			"type" 		=> 'card_not_found' ,
			"code" 		=> 'card_not_found' ,
			"message" 	=> trans('paymentError.card_not_found'),
		);
	}

	public static function deleteCard($card_id, $user_id){
		try{
			self::setApiKey();

			$stripeCustomer = \Stripe\Customer::retrieve($card_id);
			$stripeCustomer->delete();

			return array(
				"success" 	=> true
			);
		} 
		catch (Stripe\Error\Base $ex){
			$body = $ex->getJsonBody();
			$error = $body['error'] ;
			
			if(array_key_exists('code', $body)) $code = $body["code"];
			else $code = null ;
	
			return array(
				"success" 	=> false ,
				'data' => null,
				'error' => array(
					"code" 		=> ApiErrors::CARD_ERROR,
					"messages" 	=> $error['messages']
				)
			);
		}
	}

	public static function verifyStatus($account_id){
		try{
			if($account_id != null && $account_id != 'empty'){
				self::setApiKey();
				$stripeAccount = \Stripe\Account::retrieve($account_id);
				$verification = $stripeAccount->legal_entity->verification->status;
			} else{
				$verification = 'empty';
			}

			return array(
				"success"	=> true,
				"data"		=> $verification
			);
		} catch(Stripe\Error\Base $ex){
			$body = $ex->getJsonBody();
			$error = $body['error'] ;
			
			if(array_key_exists('code', $body)) $code = $body["code"];
			else $code = null ;

			return array(
				"success" 	=> false ,
				'data' => null,
				'error' => array(
					"code" 		=> ApiErrors::CARD_ERROR,
					"messages" 	=> $error
				)
			);
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

	public function pixCharge($amount, $holder, $provider = null, $providerAmount = null)
    {
        \Log::error('pix_not_implemented');
        return array(
            "success" 			=> false,
            "qr_code_base64"    => '',
            "copy_and_paste"    => '',
            "transaction_id" 	=> ''
        );
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
}