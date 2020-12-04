<?php

namespace Tests\libs\gateways;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Hash, Crypt;
use Settings, User, RequestCharging, Provider, Payment, PaymentFactory, Transaction;
use Tests\libs\gateways\GatewaysInterfaceTest;

class AllGatewaysTest extends TestCase
{
	public function testPagarme() {
		$gateway = 'pagarme';
		//Update the gateway selected
		Settings::where('key', 'default_payment')->update(['value' => $gateway]);

		//Change the keys
		Settings::where('key', 'pagarme_encryption_key')->update(['value' => 'ek_test_pHEXVtJxrD2z7BR2k3vABIcQhg9Lob']);
		Settings::where('key', 'pagarme_recipient_id')->update(['value' => 're_ckfcykeja0c5psw6eq7ohg6wx']);
		Settings::where('key', 'pagarme_api_key')->update(['value' => 'ak_test_PlTjytFjSz5RLwcUK9TFssfmKMac8y']);

		$this->runInterfaceGateways($gateway);
	}

	public function testCielo() {
		$gateway = 'cielo';
		//Update the gateway selected
		Settings::where('key', 'default_payment')->update(['value' => $gateway]);

		//Change the keys
		Settings::where('key', 'cielo_merchant_key')->update(['value' => 'BKUWHFVDBQSOTKINJCNPMCTEFZROQRFBXSHRYIPJ']);
		Settings::where('key', 'cielo_merchant_id')->update(['value' => '851c6271-df49-4ee6-b212-79ea22ae839f']);

		$this->runInterfaceGateways($gateway);
	}

	public function testBraspag() {
		$gateway = 'braspag';
		//Update the gateway selected
		Settings::where('key', 'default_payment')->update(['value' => $gateway]);

		//Change the keys
		Settings::where('key', 'braspag_merchant_id')->update(['value' => 'b330b05f-b27f-4497-a682-943b392a2b96']);
		Settings::where('key', 'braspag_merchant_key')->update(['value' => 'ULMLYDYEXNPHUZZPHUHIFXAWZTWTJUIMUZHZVUAT']);

		$this->runInterfaceGateways($gateway);
	}

	public function testGetnet() {
		$gateway = 'getnet';
		//Update the gateway selected
		Settings::where('key', 'default_payment')->update(['value' => $gateway]);

		//Change the keys
		Settings::where('key', 'getnet_seller_id')->update(['value' => '7f7ff5c9-55cb-4615-aab0-172912cb1a5e']);
		Settings::where('key', 'getnet_client_secret')->update(['value' => 'a031a735-c9c5-47f6-9213-5ae9fde11f14']);
		Settings::where('key', 'getnet_client_id')->update(['value' => 'f515ac12-4fcc-4fef-99cd-ed4071f9df3d']);

		$this->runInterfaceGateways($gateway);
	}

	public function testStripe() {
		$gateway = 'stripe';
		//Update the gateway selected
		Settings::where('key', 'default_payment')->update(['value' => $gateway]);

		//Change the keys
		Settings::where('key', 'stripe_publishable_key')->update(['value' => 'pk_test_2qw9BEvjm0jM9aedKNONjMXD']);
		Settings::where('key', 'stripe_secret_key')->update(['value' => 'sk_test_9ubwHUZJ6bnzNC6HWTkjyF3A']);
		Settings::where('key', 'stripe_total_split_refund')->update(['value' => 'true']);
		Settings::where('key', 'stripe_connect')->update(['value' => 'standard_accounts']);

		$this->runInterfaceGateways($gateway, '4242424242424242');
	}

	public function testZoop() {
		$gateway = 'zoop';
		//Update the gateway selected
		Settings::where('key', 'default_payment')->update(['value' => $gateway]);

		//Change the keys
		Settings::where('key', 'zoop_seller_id')->update(['value' => '193666ba1509419485fcafc253aff514']);
		Settings::where('key', 'zoop_publishable_key')->update(['value' => 'zpk_test_jP4JOy1OHvgJ3dyW6MHLrvgL']);
		Settings::where('key', 'zoop_marketplace_id')->update(['value' => 'c7bbd8b1b7574077804948faa27ff903']);

		echo "\n".$gateway." - Precisa terminar a implementação";
		// $this->runInterfaceGateways($gateway);
	}

	public function testGerencianet() {
		$gateway = 'gerencianet';
		//Update the gateway selected
		Settings::where('key', 'default_payment')->update(['value' => $gateway]);

		//Change the keys
		Settings::where('key', 'gerencianet_client_secret')->update(['value' => 'Client_Secret_a657286e5962569d206f245a16d75108f8a89ea1']);
		Settings::where('key', 'gerencianet_client_id')->update(['value' => 'Client_Id_43f0ec07a5d2ff067527ce82c97505018c7bd040']);
		Settings::where('key', 'gerencianet_sandbox')->update(['value' => 'true']);

		echo "\n".$gateway." - Precisa terminar a implementação";
		// $this->runInterfaceGateways($gateway);
	}


	public function testDirectpay() {
		$gateway = 'directpay';
		//Update the gateway selected
		Settings::where('key', 'default_payment')->update(['value' => $gateway]);

		//Change the keys
		Settings::where('key', 'directpay_requester_token')->update(['value' => 'Znm2YRUct8rEYgghCAq75u/NZlxbnPkCjl6eVkTUj48crOrMyoYTChb9EvdZsOoXz6h6Y46VaBRrAIs5wICTNTiliqezP/boxGVHz7mhh802uZA8sAkusfN/HsNd8kAOVoq2SWObPHI8JPxxOPVbzZMTD7+UsYqoCW70w6JafALsylfvn+mqeHnaFfF8bDQkpskAhP4KCg24zU3OxapJe1klaFkcd5VzpZu4m4bOPGHoo4NncXpJI4Fa2w73gjqtf2PSEW+s/gKMNHw49zROyvqAL9uQu4OAlg3rqYNOLYwtbioxWMHtf78ZONhb2OKfFR']);
		Settings::where('key', 'directpay_requester_password')->update(['value' => 'password']);
		Settings::where('key', 'directpay_requester_id')->update(['value' => 'op_go_homl']);
		Settings::where('key', 'directpay_encrypt_value')->update(['value' => '251381']);
		Settings::where('key', 'directpay_encrypt_key')->update(['value' => '58DFC6D0C524FB9F3B331C73DB21E356']);

		echo "\n".$gateway." - Precisa terminar a implementação";
		// $this->runInterfaceGateways($gateway);
	}

	public function testBancard() {
		$gateway = 'bancard';
		//Update the gateway selected
		Settings::where('key', 'default_payment')->update(['value' => $gateway]);

		//Change the keys
		Settings::where('key', 'bancard_private_key')->update(['value' => 'jMkXA.zxQ4ibV9XcV+ENN1awDb3mWUrt3KAkBoWf']);
		Settings::where('key', 'bancard_public_key')->update(['value' => 'BQzqR40EGL7jcA5si2AG7QUW6H4G65ip']);
		
		echo "\n".$gateway." - Precisa terminar a implementação";
		// $this->runInterfaceGateways($gateway);
	}

	public function testTransbank() {
		$gateway = 'transbank';
		//Update the gateway selected
		Settings::where('key', 'default_payment')->update(['value' => $gateway]);

		//Change the keys
		Settings::where('key', 'transbank_public_cert')->update(['value' => '123']);
		Settings::where('key', 'transbank_commerce_code')->update(['value' => '456']);
		Settings::where('key', 'transbank_private_key')->update(['value' => '789']);

		echo "\n".$gateway." - Precisa terminar a implementação";
		// $this->runInterfaceGateways($gateway);
	}


    private function runInterfaceGateways($gateway, $cardNumber = '5420222734962070'){
		$interface = new GatewaysInterfaceTest();
		
		//Cria o cartao
		$createCard = $interface->testCreateCard($cardNumber);
		$this->assertTrue($createCard['success']);
		echo "\n".$gateway." - Criar cartao: ok";

		$cardId = $createCard['payment']['id'];
		
		//Realiza uma cobranca direta e sem split
		$charge = $interface->testCharge($cardId);
		$this->assertTrue($charge['success']);
		$this->assertEquals($charge['status'], 'paid');
		echo "\n".$gateway." - charge sem split: ok";

		//Realiza uma pre-autorizacao (chargeNoCapture) sem split
		$chargeNoCapture = $interface->testChargeNoCapture($cardId);
		$this->assertTrue($chargeNoCapture['success']);
		$this->assertEquals($chargeNoCapture['status'], 'authorized');
		echo "\n".$gateway." - chargeNoCapture sem split: ok";

		//Faz o capture da pre-autorizacao anterior. Passa como parametro a transaction_id da pre-autorizacao.
		$capture = $interface->testCapture($chargeNoCapture['transaction_id'], $cardId);
		$this->assertTrue($capture['success']);
		$this->assertEquals($capture['status'], 'paid');
		echo "\n".$gateway." - capture sem split: ok";

		//Faz o cancelamento da transacao
		$refund = $interface->testRefund($chargeNoCapture['transaction_id'], $cardId);
		$this->assertTrue($refund['success']);
		$this->assertEquals($refund['status'], 'refunded');
		echo "\n".$gateway." - refunded: ok";

		//retrieve (recuperar os dados) a transaction
		$retrieve = $interface->testRetrieve($chargeNoCapture['transaction_id'], $cardId);
		$this->assertTrue($retrieve['success']);
		echo "\n".$gateway." - retrieve: ok";


		//billetCharge (boleto bancario)
		if($gateway != 'stripe') { //stripe nao possui boleto, entao nao eh verificado no teste
			$billet = $interface->testBilletCharge();
			$this->assertTrue($billet['success']);
			$this->assertInternalType('string', $billet['billet_url']);
			echo "\n".$gateway." - billet: ok - url: " . $billet['billet_url'];
		} else {
			echo "\n".$gateway." - billet: nao possui boleto";
		}
    }
}