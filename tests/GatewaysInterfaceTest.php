<?php

namespace Tests\libs\gateways;

use Hash, Crypt;
use Settings, User, RequestCharging, Provider, Payment, PaymentFactory, Transaction, ProviderStatus, ProviderType, ProviderServices, LedgerBankAccount, Ledger;
use Grimzy\LaravelMysqlSpatial\Types\Point;

class GatewaysInterfaceTest {

    public function testCreateCard($cardNumber, $isCarto = false){
		$cardExpirationMonth = 8;
		$cardExpirationYear = 2026 ;
		$cardCvv = "314";
		$cardHolder = "cartao teste";	
		$user = $this->userRandomForTest();

		$response = Payment::createCardByGateway($user->id, $cardNumber, $cardHolder, $cardExpirationMonth, $cardExpirationYear, $cardCvv, "123456", $isCarto);
		
		//o gateway da juno precisa de webview (iframe) para cadastrar cartao. Entao para prosseguir com os testes, foi colocado o token do cartao manualmente
		if(Settings::findByKey('default_payment') == 'juno') {
			$payment = Payment::find($response['payment']['id']);
			$payment->customer_id = "552fa250-f7e6-42a1-89b7-149cb2e034fe";
			$payment->card_token = "552fa250-f7e6-42a1-89b7-149cb2e034fe";
			$payment->save();
		}

		return($response);
    }

    public function testCharge($cardId, $isCarto = false)
    {
		$value = 3.28;
		if($isCarto) {
			$gateway = PaymentFactory::cartoGateway();
		} else {
			$gateway = PaymentFactory::createGateway();
		}
		
		$user = $this->userRandomForTest();
		$payment = Payment::getFirstOrDefaultPayment($user->id, $cardId);
		$response = $gateway->charge($payment, $value, 'payment test', true);

		if($response['success'] && $response['status'] == 'paid') {
			//Salva a transaction no banco
			$transaction 					= new Transaction();
			$transaction->type 				= 'request_price';
			$transaction->status 			= 'paid';
			$transaction->gross_value 	 	= $value;
			$transaction->provider_value 	= 0;
			$transaction->gateway_tax_value = 0;
			$transaction->net_value 		= 0;
			$transaction->gateway_transaction_id = $response["transaction_id"];
			$transaction->save();
		}
		return $response;
	}
	
    public function testChargeNoCapture($cardId, $isCarto)
    {
		$value = 4.29;
		if($isCarto) {
			$gateway = PaymentFactory::cartoGateway();
		} else {
			$gateway = PaymentFactory::createGateway();
		}
		$user = $this->userRandomForTest();
		$payment = Payment::getFirstOrDefaultPayment($user->id, $cardId);
		$response = $gateway->charge($payment, $value, 'payment test', false);

		if($response['success'] && $response['status'] == 'authorized') {
			//Salva a transaction no banco
			$transaction 					= new Transaction();
			$transaction->type 				= 'request_price';
			$transaction->status 			= 'authorized';
			$transaction->gross_value 	 	= $value;
			$transaction->provider_value 	= 0;
			$transaction->gateway_tax_value = 0;
			$transaction->net_value 		= 0;
			$transaction->gateway_transaction_id = $response["transaction_id"];
			$transaction->save();
		}
		return $response;
	}
	
    public function testCapture($transactionId, $cardId)
    {
		$value = 12.34;
		$gateway = PaymentFactory::createGateway();
		$user = $this->userRandomForTest();
		$payment = Payment::getFirstOrDefaultPayment($user->id, $cardId);
		$transaction = Transaction::where('gateway_transaction_id', $transactionId)->first();
		$response = $gateway->capture($transaction, $value, $payment);
		if($response && $response['status'] == 'paid') {
			$transaction->status = 'paid';
			$transaction->save();
		}
		
		return $response;
	}


	public function testRefund($transactionId, $cardId)
	{
		$gateway = PaymentFactory::createGateway();
		$user = $this->userRandomForTest();
		$payment = Payment::getFirstOrDefaultPayment($user->id, $cardId);
		$transaction = Transaction::where('gateway_transaction_id', $transactionId)->first();
		$response = $gateway->refund($transaction, $payment);
		
		return $response;
	}

	public function testRetrieve($transactionId, $cardId)
	{
		$gateway = PaymentFactory::createGateway();
		$user = $this->userRandomForTest();
		$payment = Payment::getFirstOrDefaultPayment($user->id, $cardId);
		$transaction = Transaction::where('gateway_transaction_id', $transactionId)->first();
		$response = $gateway->retrieve($transaction, $payment);
		
		return $response;
	}

	public function testBilletCharge()
	{
		$value = 14.20;
		$gateway = PaymentFactory::createGateway();
		$user = $this->userRandomForTest();
		$response = $gateway->billetCharge($value, $user, config('app.url') . '/api/v3/postback', date("Y-m-d"), 'boleto de teste');

		return $response;
	}

	public function testCreateOrUpdateAccount($cardId)
    {
		$gateway = PaymentFactory::createGateway();
		$provider = $this->providerRandomForTest();
		$ledgerBankAccount = LedgerBankAccount::where('provider_id', $provider->id)->first();
		$response = $gateway->createOrUpdateAccount($ledgerBankAccount);

		if(isset($response['recipient_id']) && $response['recipient_id']) {
			$ledgerBankAccount->recipient_id = $response['recipient_id'];
			$ledgerBankAccount->save();
		}

		return $response;
	}

	public function testChargeWithSplit($cardId, $capture)
    {
		$value = 8.92;
		$provider_value = 5;
		$gateway = PaymentFactory::createGateway();
		$user = $this->userRandomForTest();
		$provider = $this->providerRandomForTest();
		$payment = Payment::getFirstOrDefaultPayment($user->id, $cardId);
		$response = $gateway->chargeWithSplit($payment, $provider, $value, $provider_value, 'payment test', $capture);

		if($response['success'] && $response['status'] == 'authorized') {
			//Salva a transaction no banco
			$transaction 					= new Transaction();
			$transaction->type 				= 'request_price';
			$transaction->status 			= 'paid';
			$transaction->gross_value 	 	= $value;
			$transaction->provider_value 	= 0;
			$transaction->gateway_tax_value = 0;
			$transaction->net_value 		= 0;
			$transaction->gateway_transaction_id = $response["transaction_id"];
			$transaction->save();
		}
		return $response;
	}
    
	public function testPixCharge() {
		$gateway = PaymentFactory::createPixGateway();
		$user = $this->userRandomForTest();
		$response = $gateway->pixCharge($user, 0.01);
		return $response;
	}
    /**
	 * get random user for tests
	 * @return User
	 **/
	private static function userRandomForTest($second = false)
	{
		if (!$second)
			$user	= User::where('email', 'user.healthcheck@codificar.com.br')->first();
		else
			$user	= User::where('email', 'user2.healthcheck@codificar.com.br')->first();

		if  ( isset($user) ) {
			return $user;
		} else {
			$newUser	= new User;
			$newUser->first_name 	= !$second ? 'User' : 'User2';
			$newUser->last_name  	= 'Health Check';
			$newUser->email 	 	= !$second ? 'user.healthcheck@codificar.com.br' : 'user2.healthcheck@codificar.com.br';
			$newUser->phone 	 	= !$second ? '+5531992845654' : '+5531992848998';
			$newUser->address 	 	= 'Rua dos Goitacazes';
			$newUser->state	 		= 'MG';
			$newUser->country 	 	= 'Brasil';
			$newUser->zipcode	 	= '30190050';
			$newUser->password 	 	= Hash::make('qweqwe');
			$newUser->token 	 	= generate_token();
			$newUser->token_expiry	= generate_expiry();
			$newUser->device_token	= generate_token();
			$newUser->device_type	= 'android';
			$newUser->login_by		= 'manual';
			$newUser->device_token	= generate_token();
			$newUser->debt			= '0.00';
			$newUser->timezone		= 'UTC';
			$newUser->document		= !$second ? '43609459069' : '57535491014';
			$newUser->gender		= 'male';
			$newUser->status_id		= 1;
			$newUser->location_id	= 0;
			$newUser->address_number= 375;
			$newUser->address_neighbour = 'Centro';
			$newUser->address_city  = 'Belo Horizonte';
			if(isset($newUser->birthdate)) {
				$newUser->birthdate = '1999-12-12';
			}
			
			$newUser->save();

			return $newUser;
		}
    }
    
    /**
     * get random provider for tests
     * @return  Provider
     * */
    private static function providerRandomForTest(){
		$provider = Provider::Where('email' , 'testegmail@gmail.com')->first();

		if ( isset($provider) ) {
			return $provider;
		} else {
			$status = ProviderStatus::where('name', 'APROVADO')->first();

			$newProvider				= new Provider;
			$newProvider->first_name 	= 'Provider';
			$newProvider->last_name  	= 'Health Check';
			$newProvider->email 	 	= 'testegmail@gmail.com';
			$newProvider->address 	 	= 'Rua dos Goitacazes';
			$newProvider->state	 		= 'MG';
			$newProvider->phone 	 	= '+5531999896532';
			$newProvider->country 	 	= 'Brasil';
			$newProvider->zipcode	 	= '30190050';
			$newProvider->password 	 	= Hash::make('qweqwe');
			$newProvider->document 	 	= '70538862041';
			$newProvider->token 	 	= generate_token();
			$newProvider->token_expiry	= generate_expiry();
			$newProvider->device_token	= generate_token();
			$newProvider->device_type	= 'android';
			$newProvider->login_by		= 'manual';
			$newProvider->device_token	= generate_token();
			$newProvider->timezone		= 'UTC';
			$newProvider->status_id		= $status->id;
			$newProvider->location_id	= null;
			$newProvider->address_number= 375;
			$newProvider->address_neighbour = 'Centro';
			$newProvider->address_city  = 'Belo Horizonte';
			$newProvider->last_activity = date('Y-m-d H:i:s');
			$newProvider->register_step = 'approved';
			$newProvider->cnh 			= '65675221000105';
			$newProvider->car_brand 	= 'Ford';
			$newProvider->car_number 	= 'QWE-0134';
			$newProvider->car_model 	= 'Focus';
			$newProvider->is_active     = 1;
			$newProvider->is_available  = 1;
			$newProvider->is_approved   = 1;
			if(isset($newProvider->position)) {
				$newProvider->position   	= new Point(-19.922324, -43.941561);
			}
			
			$newProvider->save();

			$types = ProviderType::where('is_visible', 1)->get();

			foreach ($types as $type) {
				ProviderServices::saveDb($newProvider->id, $type->id, null, null, $type->price_per_unit_distance, $type->price_per_unit_time,
						   $type->base_price, 0, $type->commission_rate, $type->commission_type, $type->base_distance, $type->base_time,
						   $type->base_price_provider, $type->base_price_user, $type->distance_unit,
						   $type->time_unit, 'none', 1);
			}

			//Create a bank cont for test
			$provider = Provider::find($newProvider->id);
			$ledger = ($provider ? $provider->createLedger() : new Ledger);


			$ledgerBankAccount = new LedgerBankAccount();
			$ledgerBankAccount->ledger_id = $ledger->id;
			$ledgerBankAccount->holder =  $provider->first_name;
			$ledgerBankAccount->document = $provider->document;
			$ledgerBankAccount->bank_id = '12';
			$ledgerBankAccount->agency = '1234';
			$ledgerBankAccount->agency_digit = 5;
			$ledgerBankAccount->account = '12345';
			$ledgerBankAccount->account_type = 'conta_corrente';
			$ledgerBankAccount->account_digit = 6;
			$ledgerBankAccount->recipient_id = 'empty';
            $ledgerBankAccount->person_type = 'person_type';
           	$ledgerBankAccount->provider_id = $provider->id;
			$ledgerBankAccount->birthday_date = '1990-10-10 00:00:00';
            $ledgerBankAccount->save();

			return $newProvider;
		}
	}
}
