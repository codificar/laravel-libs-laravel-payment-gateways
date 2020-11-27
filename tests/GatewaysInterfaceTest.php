<?php

namespace Tests\libs\gateways;

use Hash, Crypt;
use Settings, User, RequestCharging, Provider, Payment, PaymentFactory, Transaction;

class GatewaysInterfaceTest {

    public function testCreateCard(){
		$cardNumber = "5420222734962070";
		$cardExpirationMonth = 8;
		$cardExpirationYear = 2026 ;
		$cardCvv = "314";
		$cardHolder = "cartao teste";	
		$user = $this->userRandomForTest();

		$response = Payment::createCardByGateway($user->id, $cardNumber, $cardHolder, $cardExpirationMonth, $cardExpirationYear, $cardCvv);
		
		return($response);
    }

    public function testCharge($cardId)
    {
		$value = 8.92;
		$gateway = PaymentFactory::createGateway();
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
	
    public function testChargeNoCapture($cardId)
    {
		$value = 12.34;
		$gateway = PaymentFactory::createGateway();
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
			$newUser->birthdate		= '1999-12-12';

			$newUser->save();

			return $newUser;
		}
    }
    
    /**
     * get random provider for tests
     * @return  Provider
     * */
    private static function providerRandomForTest(){
		$provider = Provider::Where('email' , 'provider.healthcheck@codificar.com.br')->first();

		if ( isset($provider) ) {
			return $provider;
		} else {
			$status = ProviderStatus::where('name', 'APROVADO')->first();

			$newProvider				= new Provider;
			$newProvider->first_name 	= 'Provider';
			$newProvider->last_name  	= 'Health Check';
			$newProvider->email 	 	= 'provider.healthcheck@codificar.com.br';
			$newProvider->address 	 	= 'Rua dos Goitacazes';
			$newProvider->state	 		= 'Minas Gerais';
			$newProvider->country 	 	= 'Brasil';
			$newProvider->zipcode	 	= '30190050';
			$newProvider->password 	 	= Hash::make('qweqwe');
			$newProvider->token 	 	= generate_token();
			$newProvider->token_expiry	= generate_expiry();
			$newProvider->device_token	= generate_token();
			$newProvider->device_type	= 'android';
			$newProvider->login_by		= 'manual';
			$newProvider->device_token	= generate_token();
			$newProvider->timezone		= 'UTC';
			$newProvider->status_id		= $status->id;
			$newProvider->location_id	= 0;
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
			$newProvider->position   	= new Point(-19.922324, -43.941561);

			$newProvider->save();

			$types = ProviderType::where('is_visible', 1)->get();

			foreach ($types as $type) {
				ProviderServices::saveDb($newProvider->id, $type->id, null, null, $type->price_per_unit_distance, $type->price_per_unit_time,
						   $type->base_price, 0, $type->commission_rate, $type->commission_type, $type->base_distance, $type->base_time,
						   $type->base_price_provider, $type->base_price_user, $type->distance_unit,
						   $type->time_unit, $genre = ProviderType::NONE, 1);
			}

			return $newProvider;
		}
	}
}
