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

	const NO_CONNECT 			= 'no_connect' ;
	const CUSTOM_ACCOUNTS 		= 'custom_accounts' ;
	const EXPRESS_ACCOUNTS 		= 'express_accounts' ;
	const STANDARD_ACCOUNTS 	= 'standard_accounts' ;	

	const AUTO_TRANSFER_PROVIDER = 'auto_transfer_provider_payment';	

    public function __construct()
    {
        try {
            $this->setApiKey();
        } catch (\Exception $ex) {
            \Log::error('StripeLib: Failed to initialize - API key not configured', [
                'error' => $ex->getMessage()
            ]);
            // Não lança exceção aqui para não quebrar a instanciação
            // A validação será feita quando tentar usar a API
        }
    }	

	/**
	 * Set Stripe API Key
	 * Validates that the API key is configured before setting it
	 * 
	 * @throws \Exception if API key is not configured
	 */
	private function setApiKey(){
		$apiKey = Settings::findByKey('stripe_secret_key');
		
		if (empty($apiKey)) {
			\Log::error('Stripe API key not configured');
			throw new \Exception('Stripe API key not configured. Please set stripe_secret_key in settings.');
		}
		
		\Stripe\Stripe::setApiKey($apiKey);
	}

	/**
	 * Create a new credit card in Stripe
	 * 
	 * @deprecated Para Stripe, use createCardFromPaymentMethod() em vez deste método.
	 * Este método ainda funciona para compatibilidade, mas o Stripe não aceita mais dados brutos.
	 * 
	 * @param Payment $payment Payment object with card information
	 * @param User|null $user User object (optional, will be retrieved from payment if not provided)
	 * @return array ['success' => bool, 'customer_id' => string, 'token' => string, 'card_token' => string, 'last_four' => string, 'card_type' => string, 'gateway' => string]
	 * @throws \Stripe\Exception\ApiErrorException
	 */
	public function createCard(Payment $payment, User $user = null){
		// Aviso: Stripe não aceita mais dados brutos
		\Log::warning('StripeLib::createCard() está sendo usado com dados brutos. Use createCardFromPaymentMethod() em vez disso.');
		
		// Validações de entrada
		if (empty($payment->getCardNumber())) {
			return array(
				"success" 	=> false,
				'data' 		=> null,
				'error' 	=> array(
					"code" 		=> ApiErrors::CARD_ERROR,
					"messages" 	=> array(trans('creditCard.customerCreationFail'))
				)
			);
		}

		$cardNumber 			= $payment->getCardNumber();
		$cardExpirationMonth 	= $payment->getCardExpirationMonth();
		$cardExpirationYear 	= $payment->getCardExpirationYear();
		$cardCvc 				= $payment->getCardCvc();
		$cardHolder 			= $payment->getCardHolder();
		$stripeCustomer 		= null ;
		$createdCard 			= null ;

		try {
			// captura o usuario
			$user = $payment->user_id ? $payment->User : $payment->Provider;
			
			if (!$user) {
				return array(
					"success" 	=> false,
					'data' 		=> null,
					'error' 	=> array(
						"code" 		=> ApiErrors::CARD_ERROR,
						"messages" 	=> array(trans('creditCard.customerCreationFail'))
					)
				);
			}

			// verifica se e um cartao stripe
			if(!empty($payment->customer_id) && self::isCustomerIdFromStripe($payment->customer_id)){
				try {
					// verifica se existe
					$stripeCustomer = \Stripe\Customer::retrieve($payment->customer_id);

					// atualiza cartao e outros dados
					if($stripeCustomer && $stripeCustomer->id){
						// adiciona a nova fonte de cartao
						$cardSource = $stripeCustomer->sources->create(
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

						// Normaliza o card_Token para ter a mesma estrutura que quando criado via token
						// O objeto Card do Stripe tem propriedades: id, last4, brand, etc.
						$card_Token = array(
							"success" 		=> true,
							"token" 		=> $cardSource->id ?? '',
							"card_token" 	=> $cardSource->id ?? '',
							"card_type" 	=> isset($cardSource->brand) ? strtolower($cardSource->brand) : '',
							"last_four" 	=> $cardSource->last4 ?? ''
						);

						$stripeCustomer->description 	= $user->getFullName();
						$stripeCustomer->email 			= $user->email ;
						$stripeCustomer->save();
					}
				} catch (\Stripe\Exception\ApiErrorException $ex) {
					// Se o customer não existir, continuar para criar um novo
					\Log::warning('Stripe customer not found: ' . $ex->getMessage());
					$stripeCustomer = null;
				}
			}

			if(!$stripeCustomer)
			{
				/**
				 * Create Stripe Credit Card with Token (safe transaction).
				 */
				$card_Token = $this->createToken($cardNumber, $cardExpirationMonth, $cardExpirationYear, $cardCvc, $cardHolder);

				// Validação robusta do token
				if (!$card_Token || !$card_Token["success"]) {
					\Log::error('Stripe createCard: Token creation failed', [
						'user_id' => $user->id ?? null,
						'user_email' => $user->email ?? null,
						'token_error' => $card_Token["message"] ?? 'Unknown token error',
						'token_type' => $card_Token["type"] ?? null,
						'token_code' => $card_Token["code"] ?? null,
					]);
					
					return array(
						"success" 	=> false,
						'data' 		=> null,
						'error' 	=> array(
							"code" 		=> ApiErrors::CARD_ERROR,
							"messages" 	=> array($card_Token["message"] ?? trans('creditCard.customerCreationFail'))
						)
					);
				}

				// Validação de dados obrigatórios antes de criar o Customer
				$userEmail = $user->email ?? null;
				$userFullName = $user->getFullName() ?? '';

				// Validação de email
				if (empty($userEmail) || !filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
					\Log::error('Stripe createCard: Invalid or empty email', [
						'user_id' => $user->id ?? null,
						'user_email' => $userEmail,
					]);
					
					return array(
						"success" 	=> false,
						'data' 		=> null,
						'error' 	=> array(
							"code" 		=> ApiErrors::CARD_ERROR,
							"messages" 	=> array(trans('creditCard.customerCreationFail'))
						)
					);
				}

				// Criação do Customer com tratamento específico de exceção
				try {
					$stripeCustomer = \Stripe\Customer::create(array(
						"source"		=> $card_Token["token"],
						"description" 	=> $userFullName,
						"email" 		=> $userEmail
					));

					if (!$stripeCustomer || !isset($stripeCustomer->id)) {
						\Log::error('Stripe createCard: Customer creation returned invalid object', [
							'user_id' => $user->id ?? null,
							'user_email' => $userEmail,
						]);
						
						return array(
							"success" 	=> false,
							'data' => null,
							'error' => array(
								"code" 		=> ApiErrors::CARD_ERROR,
								"messages" 	=> array(trans('creditCard.customerCreationFail'))
							)
						);
					}
				} catch (\Stripe\Exception\InvalidRequestException $ex) {
					// Erro específico de requisição inválida (ex: token já usado, email inválido, etc.)
					$body = $ex->getJsonBody();
					$error = isset($body['error']) ? $body['error'] : null;
					
					\Log::error('Stripe createCard: InvalidRequestException when creating customer', [
						'user_id' => $user->id ?? null,
						'user_email' => $userEmail,
						'stripe_error' => $ex->getMessage(),
						'stripe_code' => $error ? ($error['code'] ?? null) : null,
						'stripe_type' => $error ? ($error['type'] ?? null) : null,
						'stripe_message' => $error ? ($error['message'] ?? null) : null,
					]);
					
					return array(
						"success" 	=> false,
						'data' => null,
						'type' => $error ? ($error['type'] ?? '') : '',
						'error' => array(
							"code" 		=> ApiErrors::CARD_ERROR,
							"messages" 	=> array($error ? ($error['message'] ?? trans('creditCard.customerCreationFail')) : trans('creditCard.customerCreationFail'))
						)
					);
				} catch (\Stripe\Exception\ApiErrorException $ex) {
					// Outros erros da API do Stripe
					$body = $ex->getJsonBody();
					$error = isset($body['error']) ? $body['error'] : null;
					
					\Log::error('Stripe createCard: ApiErrorException when creating customer', [
						'user_id' => $user->id ?? null,
						'user_email' => $userEmail,
						'stripe_error' => $ex->getMessage(),
						'stripe_code' => $error ? ($error['code'] ?? null) : null,
						'stripe_type' => $error ? ($error['type'] ?? null) : null,
						'stripe_message' => $error ? ($error['message'] ?? null) : null,
					]);
					
					return array(
						"success" 	=> false,
						'data' => null,
						'type' => $error ? ($error['type'] ?? '') : '',
						'error' => array(
							"code" 		=> ApiErrors::CARD_ERROR,
							"messages" 	=> array($error ? ($error['message'] ?? trans('creditCard.customerCreationFail')) : trans('creditCard.customerCreationFail'))
						)
					);
				}
			}

			return array(
				"success" 		=> true ,
				"customer_id" 	=> $stripeCustomer->id,
				"token"			=> $card_Token["token"],
				"card_token" 	=> $card_Token["card_token"] ,
				"last_four" 	=> $card_Token["last_four"] ,
				"card_type"		=> strtolower($card_Token["card_type"]),
				"gateway"		=> "stripe"
			);

		}
		catch (\Stripe\Exception\ApiErrorException $ex){
			// Este catch é um fallback para erros não capturados nos try/catch internos
			$body = $ex->getJsonBody();
			$error = isset($body['error']) ? $body['error'] : null;

			\Log::error('Stripe createCard: Unexpected ApiErrorException', [
				'user_id' => isset($user) ? ($user->id ?? null) : null,
				'user_email' => isset($user) ? ($user->email ?? null) : null,
				'stripe_error' => $ex->getMessage(),
				'stripe_code' => $error ? ($error['code'] ?? null) : null,
				'stripe_type' => $error ? ($error['type'] ?? null) : null,
			]);

			return array(
				"success" 	=> false ,
				'data' => null,
				'type' => $error ? ($error['type'] ?? '') : '',
				'error' => array(
					"code" 		=> ApiErrors::CARD_ERROR,
					"messages" 	=> array($error ? ($error['message'] ?? trans('creditCard.customerCreationFail')) : trans('creditCard.customerCreationFail'))
				)
			);
		}
		catch(\Throwable $th)
        {
			\Log::error('Stripe createCard unexpected error: ' . $th->getMessage());
            return array(
				"success" 	=> false ,
				'data' => null,
				'type' => '',
				'message' 	=> trans('creditCard.card_declined'),
				'error' => array(
					"code" 		=> ApiErrors::CARD_ERROR,
					"messages" 	=> array(trans('creditCard.customerCreationFail'))
				)
			);
        }

	}

	/**
	 * Create a new credit card in Stripe using Payment Method ID (modern and secure method)
	 * 
	 * This method uses Stripe Payment Methods API, which is the recommended approach
	 * for PCI compliance. The Payment Method is created in the frontend using Stripe.js
	 * and only the payment_method_id is sent to the backend.
	 * 
	 * @param Payment $payment Payment object
	 * @param string $paymentMethodId Payment Method ID created via Stripe.js
	 * @param string $cardHolder Name of the card holder
	 * @return array ['success' => bool, 'customer_id' => string, 'payment_method_id' => string, 'card_token' => string, 'last_four' => string, 'card_type' => string, 'gateway' => string]
	 * @throws \Stripe\Exception\ApiErrorException
	 */
	public function createCardFromPaymentMethod(Payment $payment, $paymentMethodId, $cardHolder) {
		try {
			$this->setApiKey();
			
			// Validar payment_method_id
			if (empty($paymentMethodId)) {
				return array(
					"success" 	=> false,
					'data' 		=> null,
					'error' 	=> array(
						"code" 		=> ApiErrors::CARD_ERROR,
						"messages" 	=> array(trans('creditCard.customerCreationFail'))
					)
				);
			}
			
			// Capturar o usuário
			$user = $payment->user_id ? $payment->User : $payment->Provider;
			
			if (!$user) {
				return array(
					"success" 	=> false,
					'data' 		=> null,
					'error' 	=> array(
						"code" 		=> ApiErrors::CARD_ERROR,
						"messages" 	=> array(trans('creditCard.customerCreationFail'))
					)
				);
			}
			
			// Validar email do usuário
			$userEmail = $user->email ?? null;
			if (empty($userEmail) || !filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
				\Log::error('Stripe createCardFromPaymentMethod: Invalid or empty email', [
					'user_id' => $user->id ?? null,
					'user_email' => $userEmail,
				]);
				
				return array(
					"success" 	=> false,
					'data' 		=> null,
					'error' 	=> array(
						"code" 		=> ApiErrors::CARD_ERROR,
						"messages" 	=> array(trans('creditCard.customerCreationFail'))
					)
				);
			}
			
			// Recuperar Payment Method do Stripe
			$paymentMethod = \Stripe\PaymentMethod::retrieve($paymentMethodId);
			
			if (!$paymentMethod || $paymentMethod->type !== 'card') {
				return array(
					"success" 	=> false,
					'data' 		=> null,
					'error' 	=> array(
						"code" 		=> ApiErrors::CARD_ERROR,
						"messages" 	=> array(trans('creditCard.customerCreationFail'))
					)
				);
			}
			
			// Verificar se o Payment Method já está anexado a um Customer
			$existingCustomerId = $paymentMethod->customer ?? null;
			
			// Verificar se já existe customer no Stripe (do payment ou do Payment Method)
			$stripeCustomer = null;
			if ($existingCustomerId) {
				// Payment Method já está anexado a um Customer, usar esse Customer
				try {
					$stripeCustomer = \Stripe\Customer::retrieve($existingCustomerId);
					
					// Atualizar dados do customer
					$stripeCustomer->description = $user->getFullName();
					$stripeCustomer->email = $userEmail;
					$stripeCustomer->save();
					
					\Log::info('Stripe: Payment Method já estava anexado ao Customer: ' . $existingCustomerId);
				} catch (\Stripe\Exception\ApiErrorException $ex) {
					\Log::warning('Stripe: Erro ao recuperar Customer existente: ' . $ex->getMessage());
					$stripeCustomer = null;
				}
			}
			
			// Se não encontrou Customer do Payment Method, verificar se há no payment
			if (!$stripeCustomer && !empty($payment->customer_id) && self::isCustomerIdFromStripe($payment->customer_id)) {
				try {
					$stripeCustomer = \Stripe\Customer::retrieve($payment->customer_id);
					
					// Atualizar dados do customer
					$stripeCustomer->description = $user->getFullName();
					$stripeCustomer->email = $userEmail;
					$stripeCustomer->save();
				} catch (\Stripe\Exception\ApiErrorException $ex) {
					\Log::warning('Stripe customer not found: ' . $ex->getMessage());
					$stripeCustomer = null;
				}
			}
			
			// Criar novo customer se não existir
			if (!$stripeCustomer) {
				$stripeCustomer = \Stripe\Customer::create([
					'email' => $userEmail,
					'description' => $user->getFullName(),
					'name' => $cardHolder,
				]);
			}
			
			// Anexar Payment Method ao Customer apenas se ainda não estiver anexado
			if (!$existingCustomerId || $existingCustomerId !== $stripeCustomer->id) {
				try {
					$paymentMethod->attach(['customer' => $stripeCustomer->id]);
				} catch (\Stripe\Exception\ApiErrorException $ex) {
					// Se já estiver anexado a outro Customer, tentar desanexar e anexar ao novo
					if (strpos($ex->getMessage(), 'already been attached') !== false && $existingCustomerId) {
						\Log::info('Stripe: Payment Method já anexado, usando Customer existente: ' . $existingCustomerId);
						// Usar o Customer existente
						$stripeCustomer = \Stripe\Customer::retrieve($existingCustomerId);
						$stripeCustomer->description = $user->getFullName();
						$stripeCustomer->email = $userEmail;
						$stripeCustomer->save();
					} else {
						throw $ex;
					}
				}
			}
			
			// Definir como Payment Method padrão do customer
			\Stripe\Customer::update($stripeCustomer->id, [
				'invoice_settings' => [
					'default_payment_method' => $paymentMethod->id
				]
			]);
			
			// Extrair informações do cartão do Payment Method
			$card = $paymentMethod->card;
			$lastFour = $card->last4 ?? '';
			$cardType = isset($card->brand) ? strtolower($card->brand) : '';
			
			// Retornar sucesso
			return array(
				"success" 			=> true,
				"customer_id" 		=> $stripeCustomer->id,
				"payment_method_id" => $paymentMethod->id,
				"card_token" 		=> $paymentMethod->id, // Para compatibilidade
				"token" 			=> $paymentMethod->id, // Para compatibilidade
				"last_four" 		=> $lastFour,
				"card_type" 		=> $cardType,
				"gateway" 			=> "stripe"
			);
			
		} catch (\Stripe\Exception\ApiErrorException $ex) {
			\Log::error('Stripe createCardFromPaymentMethod API error: ' . $ex->getMessage(), [
				'user_id' => isset($user) ? ($user->id ?? null) : null,
				'payment_method_id' => $paymentMethodId,
				'stripe_error' => $ex->getMessage(),
			]);
			
			$body = $ex->getJsonBody();
			$error = $body['error'] ?? null;
			
			return array(
				"success" 	=> false,
				'data' => null,
				'type' => $error ? ($error['type'] ?? '') : '',
				'message' => $error ? ($error['message'] ?? trans('creditCard.customerCreationFail')) : trans('creditCard.customerCreationFail'),
				'error' => array(
					"code" 		=> ApiErrors::CARD_ERROR,
					"messages" 	=> array($error ? ($error['message'] ?? trans('creditCard.customerCreationFail')) : trans('creditCard.customerCreationFail'))
				)
			);
		} catch (\Throwable $th) {
			\Log::error('Stripe createCardFromPaymentMethod unexpected error: ' . $th->getMessage());
			return array(
				"success" 	=> false,
				'data' => null,
				'type' => '',
				'message' 	=> trans('creditCard.card_declined'),
				'error' => array(
					"code" 		=> ApiErrors::CARD_ERROR,
					"messages" 	=> array(trans('creditCard.customerCreationFail'))
				)
			);
		}
	}

	public static function isCustomerIdFromStripe($customerId){
		return !(strpos($customerId, "cus_") === FALSE);
	}

	/**
	 * Charge the user service using Split with Payment Intents (modern Stripe API)
	 * Reference Link: https://stripe.com/docs/connect/charges
	 * 
	 * @param Payment $payment Payment object with customer_id
	 * @param Provider $provider Provider object with bank account
	 * @param float $totalAmount Total amount to charge
	 * @param float $providerAmount Amount to transfer to provider
	 * @param string $description Description of the charge
	 * @param bool $capture Whether to immediately capture the charge
	 * @param User|null $user User object (optional)
	 * @return array ['success' => bool, 'paid' => bool, 'status' => string, 'captured' => bool, 'transaction_id' => string]
	 * @throws \Stripe\Exception\ApiErrorException
	 */
	public function chargeWithSplit(Payment $payment, Provider $provider, $totalAmount, $providerAmount, $description, $capture = true, User $user = null)
	{
		// Validações de entrada
		if (empty($payment->customer_id)) {
			return array(
				"success" 			=> false,
				"type" 				=> 'invalid_request_error',
				"code" 				=> 'missing_customer_id',
				"message" 			=> trans('paymentError.missing_customer_id'),
				"transaction_id" 	=> ''
			);
		}

		if ($totalAmount <= 0 || $providerAmount < 0) {
			return array(
				"success" 			=> false,
				"type" 				=> 'invalid_request_error',
				"code" 				=> 'invalid_amount',
				"message" 			=> trans('paymentError.invalid_amount'),
				"transaction_id" 	=> ''
			);
		}

		// Converter valores para centavos
		$totalAmount = round($totalAmount * 100);
		$providerAmount = round($providerAmount * 100);
		
		// Verificar se o provider tem conta Stripe Connect
		$providerAccountId = null;
		if ($provider->getBankAccount()) {
			$providerAccountId = $provider->getBankAccount()->recipient_id;
		}

		try {
			$this->setApiKey();
			
			// Obter payment_method_id do payment (se disponível)
			$paymentMethodId = $payment->payment_method_id ?? null;
			
			// Se não tiver payment_method_id, tentar obter o padrão do customer
			if (empty($paymentMethodId)) {
				try {
					$customer = \Stripe\Customer::retrieve($payment->customer_id);
					$paymentMethodId = $customer->invoice_settings->default_payment_method ?? null;
					
					// Se ainda não tiver, buscar o primeiro payment method do customer
					if (empty($paymentMethodId)) {
						$paymentMethods = \Stripe\PaymentMethod::all([
							'customer' => $payment->customer_id,
							'type' => 'card',
							'limit' => 1
						]);
						
						if (!empty($paymentMethods->data)) {
							$paymentMethodId = $paymentMethods->data[0]->id;
						}
					}
				} catch (\Exception $e) {
					\Log::warning('Stripe chargeWithSplit: Erro ao buscar payment method do customer: ' . $e->getMessage());
				}
			}
			
			// Criar Payment Intent com transfer_data para split
			$paymentIntentData = [
				'amount' => $totalAmount,
				'currency' => strtolower($this->getCurrency()),
				'customer' => $payment->customer_id,
				'description' => $description,
				'confirmation_method' => 'automatic',
				'confirm' => true,
			];
			
			// Adicionar payment_method se disponível
			if (!empty($paymentMethodId)) {
				$paymentIntentData['payment_method'] = $paymentMethodId;
			}
			
			// Se não capturar imediatamente, usar manual capture
			if (!$capture) {
				$paymentIntentData['capture_method'] = 'manual';
			}
			
			// Adicionar transfer_data para split payment (Stripe Connect)
			if (!empty($providerAccountId)) {
				$paymentIntentData['transfer_data'] = [
					'destination' => $providerAccountId,
					'amount' => $providerAmount
				];
			}
			
			$paymentIntent = \Stripe\PaymentIntent::create($paymentIntentData);
			
			// Verificar se requer ação adicional (3D Secure, etc)
			if ($paymentIntent->status === 'requires_action' || $paymentIntent->status === 'requires_payment_method') {
				return array(
					"success" 			=> false,
					"type" 				=> 'card_error',
					"code" 				=> 'requires_action',
					"message" 			=> 'Payment requires additional authentication',
					"transaction_id" 	=> $paymentIntent->id,
					"client_secret" 	=> $paymentIntent->client_secret,
					"status" 			=> $paymentIntent->status
				);
			}
			
			// Verificar se falhou
			if ($paymentIntent->status === 'canceled' || $paymentIntent->status === 'payment_failed') {
				$errorMessage = 'Payment failed';
				if (!empty($paymentIntent->last_payment_error)) {
					$errorMessage = $paymentIntent->last_payment_error->message ?? 'Payment failed';
				}
				
				return array(
					"success" 			=> false,
					"type" 				=> 'card_error',
					"code" 				=> $paymentIntent->last_payment_error->code ?? 'payment_failed',
					"message" 			=> $errorMessage,
					"transaction_id" 	=> $paymentIntent->id
				);
			}
			
			// Sucesso
			$isPaid = ($paymentIntent->status === 'succeeded');
			$isCaptured = ($paymentIntent->status === 'succeeded' || $paymentIntent->status === 'requires_capture');
			
			return array(
				"success" 			=> true,
				"paid" 				=> $isPaid,
				"status" 			=> $this->getPaymentIntentStatus($paymentIntent),
				"captured" 			=> $isCaptured,
				"transaction_id" 	=> $paymentIntent->id
			);

		}
		catch(\Stripe\Exception\InvalidRequestException $ex){
			\Log::error('Stripe chargeWithSplit error: ' . $ex->getMessage());

			$body = $ex->getJsonBody();
			$error = isset($body['error']) ? $body['error'] : null;

			return array(
				"success" 			=> false ,
				"type" 				=> $error ? ($error["type"] ?? 'api_error') : 'api_error' ,
				"code" 				=> $error ? ($error["code"] ?? '') : '' ,
				"message" 			=> $error ? ($error["message"] ?? $ex->getMessage()) : $ex->getMessage() ,
				"transaction_id" 	=> ''
			);
		}
		catch(\Stripe\Exception\ApiErrorException $ex){
			\Log::error('Stripe chargeWithSplit API error: ' . $ex->getMessage());

			$body = $ex->getJsonBody();
			$error = isset($body['error']) ? $body['error'] : null;

			return array(
				"success" 			=> false ,
				"type" 				=> $error ? ($error["type"] ?? 'api_error') : 'api_error' ,
				"code" 				=> $error ? ($error["code"] ?? '') : '' ,
				"message" 			=> $error ? ($error["message"] ?? $ex->getMessage()) : $ex->getMessage() ,
				"transaction_id" 	=> ''
			);
		}
	}

	/**
	 * Charge a credit card using Payment Intents (modern Stripe API)
	 * 
	 * @param Payment $payment Payment object with customer_id and payment_method_id
	 * @param float $amount Amount to charge
	 * @param string $description Description of the charge
	 * @param bool $capture Whether to immediately capture the charge
	 * @param User|null $user User object (optional)
	 * @return array ['success' => bool, 'paid' => bool, 'status' => string, 'captured' => bool, 'transaction_id' => string]
	 * @throws \Stripe\Exception\ApiErrorException
	 */
	public function charge(Payment $payment, $amount, $description, $capture = true, User $user = null)
	{
		// Validações de entrada
		if (empty($payment->customer_id)) {
			return array(
				"success" 			=> false,
				"type" 				=> 'invalid_request_error',
				"code" 				=> 'missing_customer_id',
				"message" 			=> trans('paymentError.missing_customer_id'),
				"transaction_id" 	=> ''
			);
		}

		if ($amount <= 0) {
			return array(
				"success" 			=> false,
				"type" 				=> 'invalid_request_error',
				"code" 				=> 'invalid_amount',
				"message" 			=> trans('paymentError.invalid_amount'),
				"transaction_id" 	=> ''
			);
		}

		// Converter valor para centavos
		$amount = round($amount * 100);

		try {
			$this->setApiKey();
			
			// Obter payment_method_id do payment (se disponível)
			$paymentMethodId = $payment->payment_method_id ?? null;
			
			// Se não tiver payment_method_id, tentar obter o padrão do customer
			if (empty($paymentMethodId)) {
				try {
					$customer = \Stripe\Customer::retrieve($payment->customer_id);
					$paymentMethodId = $customer->invoice_settings->default_payment_method ?? null;
					
					// Se ainda não tiver, buscar o primeiro payment method do customer
					if (empty($paymentMethodId)) {
						$paymentMethods = \Stripe\PaymentMethod::all([
							'customer' => $payment->customer_id,
							'type' => 'card',
							'limit' => 1
						]);
						
						if (!empty($paymentMethods->data)) {
							$paymentMethodId = $paymentMethods->data[0]->id;
						}
					}
				} catch (\Exception $e) {
					\Log::warning('Stripe charge: Erro ao buscar payment method do customer: ' . $e->getMessage());
				}
			}
			
			// Criar Payment Intent
			$paymentIntentData = [
				'amount' => $amount,
				'currency' => strtolower($this->getCurrency()),
				'customer' => $payment->customer_id,
				'description' => $description,
				'confirmation_method' => 'automatic',
				'confirm' => true,
			];
			
			// Adicionar payment_method se disponível
			if (!empty($paymentMethodId)) {
				$paymentIntentData['payment_method'] = $paymentMethodId;
			}
			
			// Se não capturar imediatamente, usar manual capture
			if (!$capture) {
				$paymentIntentData['capture_method'] = 'manual';
			}
			
			$paymentIntent = \Stripe\PaymentIntent::create($paymentIntentData);
			
			// Verificar se requer ação adicional (3D Secure, etc)
			if ($paymentIntent->status === 'requires_action' || $paymentIntent->status === 'requires_payment_method') {
				return array(
					"success" 			=> false,
					"type" 				=> 'card_error',
					"code" 				=> 'requires_action',
					"message" 			=> 'Payment requires additional authentication',
					"transaction_id" 	=> $paymentIntent->id,
					"client_secret" 	=> $paymentIntent->client_secret,
					"status" 			=> $paymentIntent->status
				);
			}
			
			// Verificar se falhou
			if ($paymentIntent->status === 'canceled' || $paymentIntent->status === 'payment_failed') {
				$errorMessage = 'Payment failed';
				if (!empty($paymentIntent->last_payment_error)) {
					$errorMessage = $paymentIntent->last_payment_error->message ?? 'Payment failed';
				}
				
				return array(
					"success" 			=> false,
					"type" 				=> 'card_error',
					"code" 				=> $paymentIntent->last_payment_error->code ?? 'payment_failed',
					"message" 			=> $errorMessage,
					"transaction_id" 	=> $paymentIntent->id
				);
			}
			
			// Sucesso
			$isPaid = ($paymentIntent->status === 'succeeded');
			$isCaptured = ($paymentIntent->status === 'succeeded' || $paymentIntent->status === 'requires_capture');
			
			return array(
				"success" 			=> true,
				"paid" 				=> $isPaid,
				"status" 			=> $this->getPaymentIntentStatus($paymentIntent),
				"captured" 			=> $isCaptured,
				"transaction_id" 	=> $paymentIntent->id
			);
	
		}
		catch(\Stripe\Exception\InvalidRequestException $ex){
			\Log::error('Stripe charge error: ' . $ex->getMessage());

			$body = $ex->getJsonBody();
			$error = isset($body['error']) ? $body['error'] : null;

			return array(
				"success" 			=> false ,
				"type" 				=> $error ? ($error["type"] ?? 'api_error') : 'api_error' ,
				"code" 				=> $error ? ($error["code"] ?? '') : '' ,
				"message" 			=> $error ? ($error["message"] ?? $ex->getMessage()) : $ex->getMessage() ,
				"transaction_id" 	=> ''
			);
		}
		catch(\Stripe\Exception\ApiErrorException $ex){
			\Log::error('Stripe charge API error: ' . $ex->getMessage());

			$body = $ex->getJsonBody();
			$error = isset($body['error']) ? $body['error'] : null;

			return array(
				"success" 			=> false ,
				"type" 				=> $error ? ($error["type"] ?? 'api_error') : 'api_error' ,
				"code" 				=> $error ? ($error["code"] ?? '') : '' ,
				"message" 			=> $error ? ($error["message"] ?? $ex->getMessage()) : $ex->getMessage() ,
				"transaction_id" 	=> ''
			);
		}
	}
	
	/**
	 * Get status from Payment Intent
	 * 
	 * @param \Stripe\PaymentIntent $paymentIntent
	 * @return string
	 */
	private function getPaymentIntentStatus($paymentIntent)
	{
		switch ($paymentIntent->status) {
			case 'succeeded':
				return 'paid';
			case 'requires_capture':
				return 'authorized';
			case 'canceled':
			case 'payment_failed':
				return 'failed';
			case 'processing':
				return 'processing';
			default:
				return 'processing';
		}
	}

	/**
	 * Capture the payment of an existing, uncaptured, charge with split rules
	 * 
	 * @param Transaction $transaction Transaction object with gateway_transaction_id
	 * @param Provider $provider Provider object with bank account
	 * @param float $totalAmount Total amount to capture
	 * @param float $providerAmount Amount to transfer to provider
	 * @param Payment|null $payment Payment object (optional)
	 * @return array ['success' => bool, 'status' => string, 'captured' => bool, 'paid' => bool, 'transaction_id' => string]
	 * @throws \Stripe\Exception\ApiErrorException
	 */
	public function captureWithSplit(Transaction $transaction, Provider $provider, $totalAmount, $providerAmount, Payment $payment = null)
	{
		// Validações de entrada
		if (empty($transaction->gateway_transaction_id)) {
			return array(
				"success" 			=> false,
				"type" 				=> 'invalid_request_error',
				"code" 				=> 'missing_transaction_id',
				"message" 			=> trans('paymentError.missing_transaction_id'),
				"transaction_id" 	=> ''
			);
		}

		if ($totalAmount <= 0 || $providerAmount < 0) {
			return array(
				"success" 			=> false,
				"type" 				=> 'invalid_request_error',
				"code" 				=> 'invalid_amount',
				"message" 			=> trans('paymentError.invalid_amount'),
				"transaction_id" 	=> ''
			);
		}

		try
		{
			$charge = \Stripe\Charge::retrieve($transaction->gateway_transaction_id);

			$providerBankAccount = $provider->getBankAccount()->recipient_id;

			if($charge->destination != $providerBankAccount)
			{
				return array(
					"success" 			=> false ,
					"type" 				=> 'invalid_provider_bank_account' ,
					"code" 				=> 'invalid_provider_bank_account' ,
					"message" 			=> trans('paymentError.invalid_provider_bank_account'),
					"transaction_id" 	=> $charge->id ,
				);				
			}

			$totalAmount = round($totalAmount * 100);
			$providerAmount = round($providerAmount * 100);
			
			$charge->destination = $providerBankAccount;
			$charge->amount = $totalAmount;

			$charge->capture(
				array(
					"amount" => $totalAmount,
					"destination" 	=> array(
						"amount" 	=> $providerAmount
					)
				)
			);

			if($charge->failure_code){
				return array(
					"success" 			=> false ,
					"type" 				=> 'api_capture_error' ,
					"code" 				=> $charge->failure_code ,
					"message" 			=> trans("paymentError.".$charge->failure_code),
					"transaction_id" 	=> $charge->id ,
				);
			}			

			return array (
				'success' => true,
				'status' => $this->getStatus($charge),
				'captured' => true,
				'paid' => true,
				'transaction_id' => $charge->id
			);

		}
		catch(\Stripe\Exception\InvalidRequestException $ex)
		{
			\Log::error('Stripe captureWithSplit error: ' . $ex->getMessage());

			$body = $ex->getJsonBody();
			$error = isset($body['error']) ? $body['error'] : null;

			return array(
				"success" 			=> false ,
				"type" 				=> $error ? ($error["type"] ?? 'api_error') : 'api_error' ,
				"code" 				=> $error ? ($error["code"] ?? '') : '' ,
				"message" 			=> $error ? ($error["message"] ?? $ex->getMessage()) : $ex->getMessage() ,
				"transaction_id" 	=> ''
			);
		}
		catch(\Stripe\Exception\ApiErrorException $ex)
		{
			\Log::error('Stripe captureWithSplit API error: ' . $ex->getMessage());

			$body = $ex->getJsonBody();
			$error = isset($body['error']) ? $body['error'] : null;

			return array(
				"success" 			=> false ,
				"type" 				=> $error ? ($error["type"] ?? 'api_error') : 'api_error' ,
				"code" 				=> $error ? ($error["code"] ?? '') : '' ,
				"message" 			=> $error ? ($error["message"] ?? $ex->getMessage()) : $ex->getMessage() ,
				"transaction_id" 	=> ''
			);
		}
	}
    
	/**
	 * Capture the payment of an existing, uncaptured, charge
	 * 
	 * @param Transaction $transaction Transaction object with gateway_transaction_id
	 * @param float $amount Amount to capture
	 * @param Payment|null $payment Payment object (optional)
	 * @return array ['success' => bool, 'status' => string, 'captured' => bool, 'paid' => bool, 'transaction_id' => string]
	 * @throws \Stripe\Exception\ApiErrorException
	 */
	public function capture(Transaction $transaction, $amount, Payment $payment = null)
	{
		// Validações de entrada
		if (empty($transaction->gateway_transaction_id)) {
			return array(
				"success" 			=> false,
				"type" 				=> 'invalid_request_error',
				"code" 				=> 'missing_transaction_id',
				"message" 			=> trans('paymentError.missing_transaction_id'),
				"transaction_id" 	=> ''
			);
		}

		if ($amount <= 0) {
			return array(
				"success" 			=> false,
				"type" 				=> 'invalid_request_error',
				"code" 				=> 'invalid_amount',
				"message" 			=> trans('paymentError.invalid_amount'),
				"transaction_id" 	=> ''
			);
		}

		try
		{
			//Recupera a transacao
			$charge = \Stripe\Charge::retrieve($transaction->gateway_transaction_id);

			//Valor a ser capturado (passado por parametro)
			$amount = round($amount*100) ;

			//Nao eh possivel cobrar um valor maior que o pre-autorizado, entao nesse caso, o valor a ser cobrado sera igual ao pre-autorizado. Quem devera tratar essa logica eh o codigo que usa essa lib e nao a lib.
			if($amount > $charge->amount)
				$amount = $charge->amount;

			$chargeResponse = $charge->capture(array(
				'amount' => $amount
			));

			if($charge->failure_code){
				return array(
					"success" 			=> false ,
					"type" 				=> 'api_capture_error' ,
					"code" 				=> $charge->failure_code ,
					"message" 			=> trans("paymentError.".$charge->failure_code),
					"transaction_id" 	=> $charge->id ,
				);
			}			

			return array (
				'success' => true,
				'status' => $this->getStatus($charge),
				'captured' => true,
				'paid' => true,
				'transaction_id' => $charge->id
			);

		}
		catch(\Stripe\Exception\InvalidRequestException $ex)
		{
			\Log::error('Stripe capture error: ' . $ex->getMessage());

			$body = $ex->getJsonBody();
			$error = isset($body['error']) ? $body['error'] : null;

			return array(
				"success" 			=> false ,
				"type" 				=> $error ? ($error["type"] ?? 'api_error') : 'api_error' ,
				"code" 				=> $error ? ($error["code"] ?? '') : '' ,
				"message" 			=> $error ? ($error["message"] ?? $ex->getMessage()) : $ex->getMessage() ,
				"transaction_id" 	=> ''
			);
		}
		catch(\Stripe\Exception\ApiErrorException $ex)
		{
			\Log::error('Stripe capture API error: ' . $ex->getMessage());

			$body = $ex->getJsonBody();
			$error = isset($body['error']) ? $body['error'] : null;

			return array(
				"success" 			=> false ,
				"type" 				=> $error ? ($error["type"] ?? 'api_error') : 'api_error' ,
				"code" 				=> $error ? ($error["code"] ?? '') : '' ,
				"message" 			=> $error ? ($error["message"] ?? $ex->getMessage()) : $ex->getMessage() ,
				"transaction_id" 	=> ''
			);
		}
		catch (\Throwable $th) {
			\Log::error('Stripe capture unexpected error: ' . $th->getMessage());
			return array(
				"success" 			=> false ,
				"type" 				=> '' ,
				"code" 				=> '' ,
				"message" 			=> 'Erro desconhecido (capture stripe)' ,
				"transaction_id" 	=> ''
			);
		}

	}


	/**
	 * Refund a charge that has previously been created
	 * 
	 * @param Transaction $transaction Transaction object with gateway_transaction_id
	 * @param Payment|null $payment Payment object (optional)
	 * @return array ['success' => bool, 'status' => string, 'transaction_id' => string]
	 * @throws \Stripe\Exception\ApiErrorException
	 */
	public function refund(Transaction $transaction, Payment $payment = null){
		
		// Validações de entrada
		if (empty($transaction->gateway_transaction_id)) {
			return array(
				"success" 			=> false,
				"type" 				=> 'invalid_request_error',
				"code" 				=> 'missing_transaction_id',
				"message" 			=> trans('paymentError.missing_transaction_id'),
				"transaction_id" 	=> ''
			);
		}

		try
		{
			$refund = \Stripe\Refund::create(
				array(
					"charge" => $transaction->gateway_transaction_id
					)
				);

			if($refund->status == 'failed'){

				return array(
					"success" 			=> false ,
					"type" 				=> 'refund_error' ,
					"code" 				=> 'refund_error' ,
					"message" 			=> trans("paymentError.refund_failed"),
					"transaction_id" 	=> $refund->id ,
				);
			}

			return array(
				"success" 			=> true ,
				"status" 			=> 'refunded' ,
				"transaction_id" 	=> $refund->id ,
			);


		}
		catch(\Stripe\Exception\InvalidRequestException $ex)
		{
			\Log::error('Stripe refund error: ' . $ex->getMessage());

			$body = $ex->getJsonBody();
			$error = isset($body['error']) ? $body['error'] : null;

			return array(
				"success" 			=> false ,
				"type" 				=> $error ? ($error["type"] ?? 'api_error') : 'api_error' ,
				"code" 				=> $error ? ($error["code"] ?? '') : '' ,
				"message" 			=> $error ? ($error["message"] ?? $ex->getMessage()) : $ex->getMessage() ,
				"transaction_id" 	=> ''
			);
		}
		catch(\Stripe\Exception\ApiErrorException $ex)
		{
			\Log::error('Stripe refund API error: ' . $ex->getMessage());

			$body = $ex->getJsonBody();
			$error = isset($body['error']) ? $body['error'] : null;

			return array(
				"success" 			=> false ,
				"type" 				=> $error ? ($error["type"] ?? 'api_error') : 'api_error' ,
				"code" 				=> $error ? ($error["code"] ?? '') : '' ,
				"message" 			=> $error ? ($error["message"] ?? $ex->getMessage()) : $ex->getMessage() ,
				"transaction_id" 	=> ''
			);
		}		

	}

	/**
	 * Do refund recovering value sent to Provider
	 * 
	 * @param Transaction $transaction Transaction object with gateway_transaction_id
	 * @param Payment|null $payment Payment object (optional)
	 * @return array ['success' => bool, 'status' => string, 'transaction_id' => string]
	 * @throws \Stripe\Exception\ApiErrorException
	 */
	public function refundWithSplit(Transaction $transaction, Payment $payment = null){

		// Validações de entrada
		if (empty($transaction->gateway_transaction_id)) {
			return array(
				"success" 			=> false,
				"type" 				=> 'invalid_request_error',
				"code" 				=> 'missing_transaction_id',
				"message" 			=> trans('paymentError.missing_transaction_id'),
				"transaction_id" 	=> ''
			);
		}

		try
		{

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

			if($refund->status == 'failed'){

				return array(
					"success" 			=> false ,
					"type" 				=> 'refund_error' ,
					"code" 				=> 'refund_error' ,
					"message" 			=> trans("paymentError.refund_failed"),
					"transaction_id" 	=> $refund->id ,
				);
			}

			return array(
				"success" 			=> true ,
				"status" 			=> 'refunded' ,
				"transaction_id" 	=> $refund->id ,
			);

		}
		catch(\Stripe\Exception\InvalidRequestException $ex)
		{
			\Log::error('Stripe refundWithSplit error: ' . $ex->getMessage());

			$body = $ex->getJsonBody();
			$error = isset($body['error']) ? $body['error'] : null;

			return array(
				"success" 			=> false ,
				"type" 				=> $error ? ($error["type"] ?? 'api_error') : 'api_error' ,
				"code" 				=> $error ? ($error["code"] ?? '') : '' ,
				"message" 			=> $error ? ($error["message"] ?? $ex->getMessage()) : $ex->getMessage() ,
				"transaction_id" 	=> ''
			);
		}
		catch(\Stripe\Exception\ApiErrorException $ex)
		{
			\Log::error('Stripe refundWithSplit API error: ' . $ex->getMessage());

			$body = $ex->getJsonBody();
			$error = isset($body['error']) ? $body['error'] : null;

			return array(
				"success" 			=> false ,
				"type" 				=> $error ? ($error["type"] ?? 'api_error') : 'api_error' ,
				"code" 				=> $error ? ($error["code"] ?? '') : '' ,
				"message" 			=> $error ? ($error["message"] ?? $ex->getMessage()) : $ex->getMessage() ,
				"transaction_id" 	=> ''
			);
		}			

	}	

	public function retrieve(Transaction $transaction, Payment $payment = null){

		$stripeTransaction = \Stripe\Charge::retrieve($transaction->gateway_transaction_id);

		return array(
			'success' => true,
			'transaction_id' => $stripeTransaction->id,
			'amount' => $stripeTransaction->amount,
			'destination' => $stripeTransaction->destination ? $stripeTransaction->destination : '',
			'status' => $this->getStatus($stripeTransaction),
			'card_last_digits' => $stripeTransaction->source->last4,
		);		
	}	

	/**
	 * Upload identity document to Stripe for account verification
	 * 
	 * @param string|null $filePath Path to the file to upload
	 * @param string|null $accountId Stripe account ID (optional)
	 * @return array ['success' => bool, 'file_token' => string|null, 'type' => string, 'message' => string]
	 * @throws \Stripe\Exception\ApiErrorException
	 */
	public static function uploadIdentityDocument($filePath = null, $accountId = null){

		$return = [];

		if(Settings::getStripeConnect() != self::CUSTOM_ACCOUNTS)
		{
				return array(
					"success" 					=> true ,
					"file_token" 				=> null,
			);		
		}

		// Validações de entrada
		if (empty($filePath) || !file_exists($filePath)) {
			return array(
				"success" 					=> false,
				"file_token" 				=> 'empty',
				"type" 						=> 'invalid_request_error',
				"message" 					=> trans('paymentError.invalid_file_path'),
			);
		}				

		 try{
			$uploadData = array(
					"purpose" 	=> "identity_document",
					"file" 		=> fopen($filePath, 'r')
				);

			// Adiciona stripe_account se fornecido
			if ($accountId) {
				$uploadData["stripe_account"] = $accountId;
			}

			\Stripe\Stripe::setApiKey(Settings::findByKey('stripe_secret_key'));

			$file = \Stripe\File::create($uploadData);

			$return['file_token'] = $file->id;
			
			return array(
					"success" 					=> true ,
					"file_token" 				=> $return['file_token'],
			);
			
		}		
		catch(\Stripe\Exception\InvalidRequestException $ex){
			\Log::error('Stripe uploadIdentityDocument error: ' . $ex->getMessage());
			
			$body = $ex->getJsonBody();
			$error = isset($body['error']) ? $body['error'] : null;
			
			return array(
				"success" 					=> false,
				"file_token" 				=> 'empty',
				"type" 						=> $error ? ($error["type"] ?? 'api_error') : 'api_error',
				"message" 					=> $error ? ($error["message"] ?? $ex->getMessage()) : $ex->getMessage(),
			);
		}
		catch(\Stripe\Exception\ApiErrorException $ex){
			\Log::error('Stripe uploadIdentityDocument API error: ' . $ex->getMessage());
			
			$body = $ex->getJsonBody();
			$error = isset($body['error']) ? $body['error'] : null;
			
			return array(
				"success" 					=> false,
				"file_token" 				=> 'empty',
				"type" 						=> $error ? ($error["type"] ?? 'api_error') : 'api_error',
				"message" 					=> $error ? ($error["message"] ?? $ex->getMessage()) : $ex->getMessage(),
			);
		}
	}

	public function createOrUpdateAccount(LedgerBankAccount $ledgerBankAccount){

		$currency = $this->getCurrency();

		switch ($currency) {
			case 'USD':
				$country = 'US';
				break;
			
			default:
				$country = 'BR';	
				break;
		}
		
		
		$return = [];

		if(Settings::getStripeConnect() != self::CUSTOM_ACCOUNTS)
		{
			return array(
				"success" 					=> true ,
				"recipient_id"				=> '',
				"message_error"				=> null);			
		}		

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
		
		$birthday_year = date('Y', strtotime($ledgerBankAccount->birthday_date));
		$birthday_month = date('m', strtotime($ledgerBankAccount->birthday_date));
		$birthday_day = date('d', strtotime($ledgerBankAccount->birthday_date));

		if(!$ledgerBankAccount->birthday_date)
		{
			return array(
				"success" 					=> false ,
				"recipient_id"				=> 'empty',
				"message_error"				=> trans('bank_account.invalid_birthday'));			
		}

		$accountData = array(
			'type' => 'custom',
			'country' => $country,
			'external_account' => array(
				'object' 	=> 'bank_account',
				'country' 	=> $country,
				'currency' 	=> $currency,
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

			//\Log::debug(print_r($account,1));
		}

		if(!$account) {
			$dataStripe = $account = \Stripe\Account::create($accountData);
		}
		else{
			$account->external_account = array(
					'object' => 'bank_account',
					'country' => $country,
					'currency' => $currency,
					'routing_number' => $ledgerBankAccount->getRoutingNumber(),
					'account_number' => $ledgerBankAccount->getAccountNumber()
				);
			
			$birthday_year = date('Y', strtotime($ledgerBankAccount->birthday_date));
			$birthday_month = date('m', strtotime($ledgerBankAccount->birthday_date));
			$birthday_day = date('d', strtotime($ledgerBankAccount->birthday_date));
	
			if(!$ledgerBankAccount->birthday_date)
			{
				return array(
					"success" 					=> false ,
					"recipient_id"				=> 'empty',
					"message_error"				=> trans('bank_account.invalid_birthday'));			
			}				

			$account->legal_entity->type 							= $ledgerBankAccount->person_type;

			if($account->legal_entity->verification->status != 'verified')
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
			} catch(\Stripe\Exception\InvalidRequestException $ex){
				\Log::error('Stripe createOrUpdateAccount save error: ' . $ex->getMessage());
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
			catch(\Stripe\Exception\ApiErrorException $ex){
				\Log::error('Stripe createOrUpdateAccount API error: ' . $ex->getMessage());
				$body = $ex->getJsonBody();
				$error = trans('setting.bank_stripe_error');

				if(isset($body['error']['message'])){
					$error = trans("setting." . str_replace(" ", "_", $body['error']['message']));
				}

				$return = array(
					"success" 					=> false,
					"recipient_id"				=> isset($account->id) ? $account->id : 'empty',
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

	/**
	 * Create a Stripe token from card information
	 * 
	 * @deprecated O Stripe não aceita mais dados brutos de cartão. Use Payment Methods criados via Stripe Elements no frontend.
	 * Este método ainda funciona para contas antigas que têm acesso às APIs de dados brutos habilitado,
	 * mas não é recomendado para novas implementações.
	 * 
	 * @param string $cardNumber Card number
	 * @param int $cardExpirationMonth Expiration month
	 * @param int $cardExpirationYear Expiration year
	 * @param string $cardCvc Card CVC
	 * @param string $cardHolder Card holder name
	 * @return array ['success' => bool, 'token' => string, 'card_token' => string, 'card_type' => string, 'last_four' => string, 'type' => string, 'code' => string, 'message' => string]
	 * @throws \Stripe\Exception\ApiErrorException
	 */
	private function createToken($cardNumber, $cardExpirationMonth, $cardExpirationYear, $cardCvc, $cardHolder){
		\Log::warning('StripeLib::createToken() está sendo usado. O Stripe não aceita mais dados brutos. Use Payment Methods em vez disso.');

		try {
			$token = \Stripe\Token::create(
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
			);
		}
		catch(\Stripe\Exception\CardException $ex){
			\Log::error('Stripe createToken card error: ' . $ex->getMessage());
			$body = $ex->getJsonBody();
			$error = isset($body["error"]) ? $body["error"] : null;
			
			return array(
				"success" 				=> false ,
				"type" 					=> $error ? ($error["type"] ?? 'card_error') : 'card_error',
				"code" 					=> $error ? ($error["code"] ?? '') : '',
				"message" 				=> $error ? trans("paymentError.".$error["code"]) : $ex->getMessage(),
			);
		}
		catch(\Stripe\Exception\ApiErrorException $ex){
			\Log::error('Stripe createToken API error: ' . $ex->getMessage());
			$body = $ex->getJsonBody();
			$error = isset($body["error"]) ? $body["error"] : null;
			
			return array(
				"success" 				=> false ,
				"type" 					=> $error ? ($error["type"] ?? 'api_error') : 'api_error',
				"code" 					=> $error ? ($error["code"] ?? '') : '',
				"message" 				=> $error ? ($error["message"] ?? $ex->getMessage()) : $ex->getMessage(),
			);
		}
	}	

	public function getNextCompensationDate(){
		$carbon = Carbon::now();
		$compDays = Settings::findByKey('compensate_provider_days');
		$addDays = ($compDays || (string)$compDays == '0') ? (int)$compDays : 31;
		$carbon->addDays($addDays);
		
		return $carbon;
	}


	/**
	 * Delete a credit card from Stripe
	 * 
	 * @param Payment $payment Payment object with customer_id
	 * @param User|null $user User object (optional)
	 * @return array ['success' => bool, 'data' => null, 'error' => array]
	 * @throws \Stripe\Exception\ApiErrorException
	 */
	public function deleteCard(Payment $payment, User $user = null){
		// Validações de entrada
		if (empty($payment->customer_id)) {
			return array(
				"success" 	=> false,
				'data' 		=> null,
				'error' 	=> array(
					"code" 		=> ApiErrors::CARD_ERROR,
					"messages" 	=> array(trans('creditCard.customerNotFound'))
				)
			);
		}

		try{
			self::setApiKey();

			$stripeCustomer = \Stripe\Customer::retrieve($payment->customer_id);
			$stripeCustomer->delete();

			return array(
				"success" 	=> true
			);
		} 
		catch (\Stripe\Exception\ApiErrorException $ex){
			\Log::error('Stripe deleteCard error: ' . $ex->getMessage());
			
			$body = $ex->getJsonBody();
			$error = isset($body['error']) ? $body['error'] : null;
	
			return array(
				"success" 	=> false ,
				'data' => null,
				'error' => array(
					"code" 		=> ApiErrors::CARD_ERROR,
					"messages" 	=> $error ? ($error['message'] ?? $ex->getMessage()) : $ex->getMessage()
				)
			);
		}
	}

	/**
	 * Verify the status of a Stripe Connect account
	 * 
	 * @param string|null $account_id Stripe account ID
	 * @return array ['success' => bool, 'data' => string, 'error' => array]
	 * @throws \Stripe\Exception\ApiErrorException
	 */
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
		} catch(\Stripe\Exception\ApiErrorException $ex){
			\Log::error('Stripe verifyStatus error: ' . $ex->getMessage());
			
			$body = $ex->getJsonBody();
			$error = isset($body['error']) ? $body['error'] : null;

			return array(
				"success" 	=> false ,
				'data' => null,
				'error' => array(
					"code" 		=> ApiErrors::CARD_ERROR,
					"messages" 	=> $error ? ($error['message'] ?? $ex->getMessage()) : $ex->getMessage()
				)
			);
		}
	}

	private function getStatus($stripeTransaction)
	{
		$status = 'processing';

		if($stripeTransaction->refunded)
		{
			$status = 'refunded';
		}
		else if($stripeTransaction->paid)
		{
			if($stripeTransaction->captured)
				$status = 'paid';
			else
				$status = 'authorized';
		}
		
		return($status);
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
        try
        {
            if(Settings::findByKey(self::AUTO_TRANSFER_PROVIDER) == "1" && Settings::getStripeConnect() == self::CUSTOM_ACCOUNTS)
                return(true);
            else
                return(false);
        }
        catch(\Exception $ex)
        {
            \Log::error('Stripe checkAutoTransferProvider error: ' . $ex->getMessage());

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
	
	public function billetCharge($amount, $client, $postbackUrl, $billetExpirationDate, $billetInstructions)
	{
		\Log::error('billet_charge_not_implemented_in_stripe_gateway');

		return array (
			'success' => false,
			'captured' => false,
			'paid' => false,
			'status' => false,
			'transaction_id' => null,
			'billet_url' => '',
			'billet_expiration_date' => ''
		);
	}

	public function billetVerify($request, $transaction_id = null)
	{
		\Log::error('billet_charge_not_implemented_in_stripe_gateway');

		return array (
			'success' => false,
			'captured' => false,
			'paid' => false,
			'status' => false,
			'transaction_id' => null,
			'billet_url' => '',
			'billet_expiration_date' => ''
		);
	}

	public function pixCharge($amount, $holder)
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

	/**
	 * Create a transfer to a connected account
	 * 
	 * @param float $amount Amount to transfer (in currency units, not cents)
	 * @param string $currency Currency code (e.g., 'usd', 'brl')
	 * @param string $destination Stripe account ID of the destination
	 * @param array $metadata Additional metadata to attach to the transfer
	 * @return array ['success' => bool, 'transfer_id' => string|null, 'error' => string|null]
	 * @throws \Stripe\Exception\ApiErrorException
	 */
	public function createTransfer($amount, $currency, $destination, $metadata = [])
	{
		// Validações de entrada
		if (empty($destination)) {
			return array(
				'success' => false,
				'transfer_id' => null,
				'error' => trans('paymentError.missing_destination')
			);
		}

		if ($amount <= 0) {
			return array(
				'success' => false,
				'transfer_id' => null,
				'error' => trans('paymentError.invalid_amount')
			);
		}

		if (empty($currency)) {
			$currency = $this->getCurrency();
		}

		try {
			$transfer = \Stripe\Transfer::create([
				'amount' => round($amount * 100), // Converter para centavos
				'currency' => strtolower($currency),
				'destination' => $destination,
				'metadata' => $metadata,
			]);

			return array(
				'success' => true,
				'transfer_id' => $transfer->id,
				'error' => null
			);
		} catch(\Stripe\Exception\InvalidRequestException $ex) {
			\Log::error('Stripe createTransfer error: ' . $ex->getMessage());

			$body = $ex->getJsonBody();
			$error = isset($body['error']) ? $body['error'] : null;

			return array(
				'success' => false,
				'transfer_id' => null,
				'error' => $error ? ($error['message'] ?? $ex->getMessage()) : $ex->getMessage()
			);
		} catch(\Stripe\Exception\ApiErrorException $ex) {
			\Log::error('Stripe createTransfer API error: ' . $ex->getMessage());

			$body = $ex->getJsonBody();
			$error = isset($body['error']) ? $body['error'] : null;

			return array(
				'success' => false,
				'transfer_id' => null,
				'error' => $error ? ($error['message'] ?? $ex->getMessage()) : $ex->getMessage()
			);
		}
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
