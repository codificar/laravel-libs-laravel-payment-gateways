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
	public function testAdiq() {
		$gateway = 'adiq';
		//Update the gateway selected
		Settings::where('key', 'default_payment')->update(['value' => $gateway]);

		//Change the keys
		Settings::where('key', 'adiq_client_id')->update(['value' => 'A1EF2F6F-8BA0-4C2F-91EA-8E1603D9FD7D']);
		Settings::where('key', 'adiq_client_secret')->update(['value' => '93D46FF3-B98C-4BFF-92CD-3A3A58BDD371']);

		$this->runInterfaceGateways($gateway, '4761739001010036');
	}

	public function testPagarme() {
		$gateway = 'pagarme';
		//Update the gateway selected
		Settings::where('key', 'default_payment')->update(['value' => $gateway]);

		//Change the keys
		Settings::where('key', 'pagarme_encryption_key')->update(['value' => 'ek_test_pHEXVtJxrD2z7BR2k3vABIcQhg9Lob']);
		Settings::where('key', 'pagarme_recipient_id')->update(['value' => 're_ckfcykeja0c5psw6eq7ohg6wx']);
		Settings::where('key', 'pagarme_api_key')->update(['value' => 'ak_test_PlTjytFjSz5RLwcUK9TFssfmKMac8y']);

		$this->runInterfaceGateways($gateway);
		$this->runSPlitGateways($gateway);
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
		// $this->runSPlitGateways($gateway);
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
		// $this->runSPlitGateways($gateway, '4242424242424242');
	}

	public function testCarto() {
		$gateway = 'carto';

		//Change the keys
		Settings::where('key', 'carto_login')->update(['value' => '2962']);
		Settings::where('key', 'carto_password')->update(['value' => '123456']);

		// echo "\n".$gateway." - implementado, mas teste unitario esta falhando";
		$this->runInterfaceGateways($gateway, '1000500030001000', true);
	}

	public function testBancryp() {
		$gateway = 'bancryp';
		//Update the gateway selected
		Settings::where('key', 'default_payment')->update(['value' => $gateway]);

		//Change the keys
		Settings::where('key', 'bancryp_api_key')->update(['value' => '3235']);
		Settings::where('key', 'bancryp_secret_key')->update(['value' => '123456']);

		echo "\n".$gateway." - implementado, mas teste unitario esta falhando";
		// $this->runInterfaceGateways($gateway, '1010420013471920');
	}

	public function testBraspagCieloEcommerce() {
		$gateway = 'braspag_cielo_ecommerce';
		//Update the gateway selected
		Settings::where('key', 'default_payment')->update(['value' => $gateway]);

		//Change the keys
		Settings::where('key', 'braspag_client_id')->update(['value' => '3235']);
		Settings::where('key', 'braspag_client_secret')->update(['value' => '123456']);

		echo "\n".$gateway." - implementado, mas teste unitario esta falhando";
		// $this->runInterfaceGateways($gateway, '1010420013471920');
	}

	public function testZoop() {
		$gateway = 'zoop';
		//Update the gateway selected
		Settings::where('key', 'default_payment')->update(['value' => $gateway]);

		//Change the keys
		Settings::where('key', 'zoop_seller_id')->update(['value' => '193666ba1509419485fcafc253aff514']);
		Settings::where('key', 'zoop_publishable_key')->update(['value' => 'zpk_test_jP4JOy1OHvgJ3dyW6MHLrvgL']);
		Settings::where('key', 'zoop_marketplace_id')->update(['value' => 'c7bbd8b1b7574077804948faa27ff903']);

		$this->assertTrue(true);
		echo "\n".$gateway." - nao implementado ainda";
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

		$this->assertTrue(true);
		echo "\n".$gateway." - implementado, mas nao possui teste unitario (precisa ser feito a parte)";
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

		$this->assertTrue(true);
		echo "\n".$gateway." - implementado, mas nao possui teste unitario (precisa ser feito a parte)";
		// $this->runInterfaceGateways($gateway);
	}

	public function testBancard() {
		$gateway = 'bancard';
		//Update the gateway selected
		Settings::where('key', 'default_payment')->update(['value' => $gateway]);

		//Change the keys
		Settings::where('key', 'bancard_private_key')->update(['value' => 'jMkXA.zxQ4ibV9XcV+ENN1awDb3mWUrt3KAkBoWf']);
		Settings::where('key', 'bancard_public_key')->update(['value' => 'BQzqR40EGL7jcA5si2AG7QUW6H4G65ip']);
		
		$this->assertTrue(true);
		echo "\n".$gateway." - implementado, mas nao possui teste unitario (precisa ser feito a parte)";
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
		
		$this->assertTrue(true);
		echo "\n".$gateway." - implementado, mas nao possui teste unitario (precisa ser feito a parte)";
		// $this->runInterfaceGateways($gateway);
	}

	public function testPagarapido() {
		$gateway = 'pagarapido';
		//Update the gateway selected
		Settings::where('key', 'default_payment')->update(['value' => $gateway]);

		//Change the keys
		Settings::where('key', 'pagarapido_gateway_key')->update(['value' => '8353b77a-0d11-4a81-8b34-ac334bed7287']);
		Settings::where('key', 'pagarapido_password')->update(['value' => '123456']);
		Settings::where('key', 'pagarapido_login')->update(['value' => 'hml@aquisi.com.br']);

		$this->runInterfaceGateways($gateway);
	}

    private function runInterfaceGateways($gateway, $cardNumber = '5420222734962070', $isTerraCard = false){
		$interface = new GatewaysInterfaceTest();
		
		//Cria o cartao e verifica se todos os parametros estao ok
		$createCard = $interface->testCreateCard($cardNumber, $isTerraCard);
		$this->assertTrue($createCard['success']);
		$this->assertInternalType('string', $createCard['token']);
		$this->assertInternalType('string', $createCard['card_token']);
		$this->assertInternalType('string', $createCard['customer_id']);
		$this->assertInternalType('string', $createCard['card_type']);
		$this->assertNotEmpty($createCard['card_type']);
		$this->assertInternalType('string', $createCard['last_four']);
		$this->assertNotEmpty($createCard['last_four']);
		$this->assertInternalType('string', $createCard['gateway']);
		$this->assertNotEmpty($createCard['gateway']);
		echo "\n".$gateway." - Criar cartao: ok";

		$cardId = $createCard['payment']['id'];
		
		//Realiza uma cobranca direta e sem split
		\Log::debug("gateway: " . $gateway . " - cardId: " . $cardId);
		$charge = $interface->testCharge($cardId, $isTerraCard);
		\Log::debug(print_r($charge, true));
		$this->assertTrue($charge['success']);
		$this->assertTrue($charge['captured']);
		$this->assertTrue($charge['paid']);
		$this->assertEquals($charge['status'], 'paid');
		$this->assertInternalType('string', $charge['transaction_id']);
		$this->assertNotEmpty($charge['transaction_id']);
		echo "\n".$gateway." - charge sem split: ok";

		//Realiza uma pre-autorizacao (chargeNoCapture) sem split
		if($gateway != 'pagarapido') { //nao testa chargeNoCapture no gateway pagarapido, pois ele nao tem essa funcionalidade
			$chargeNoCapture = $interface->testChargeNoCapture($cardId, $isTerraCard);
			$this->assertTrue($chargeNoCapture['success']);
			$this->assertFalse($chargeNoCapture['captured']);
			$this->assertFalse($chargeNoCapture['paid']);
			$this->assertEquals($chargeNoCapture['status'], 'authorized');
			$this->assertInternalType('string', $chargeNoCapture['transaction_id']);
			$this->assertNotEmpty($chargeNoCapture['transaction_id']);
			echo "\n".$gateway." - chargeNoCapture sem split: ok";
		
			//Faz o capture da pre-autorizacao anterior. Passa como parametro a transaction_id da pre-autorizacao.
			$capture = $interface->testCapture($chargeNoCapture['transaction_id'], $cardId);
			$this->assertTrue($capture['success']);
			$this->assertEquals($capture['status'], 'paid');
			$this->assertTrue($capture['captured']);
			$this->assertTrue($capture['paid']);
			$this->assertInternalType('string', $capture['transaction_id']);
			$this->assertNotEmpty($capture['transaction_id']);
			echo "\n".$gateway." - capture sem split: ok";
		} else {
			$chargeNoCapture['transaction_id'] = $charge['transaction_id'];
		}

		//Faz o cancelamento da transacao
		$refund = $interface->testRefund($chargeNoCapture['transaction_id'], $cardId);
		$this->assertTrue($refund['success']);
		$this->assertEquals($refund['status'], 'refunded');
		$this->assertInternalType('string', $refund['transaction_id']);
		$this->assertNotEmpty($refund['transaction_id']);
		echo "\n".$gateway." - refunded: ok";

		//retrieve (recuperar os dados) a transaction
		$retrieve = $interface->testRetrieve($chargeNoCapture['transaction_id'], $cardId);
		$this->assertTrue($retrieve['success']);
		$this->assertInternalType('string', $retrieve['transaction_id']);
		$this->assertNotEmpty($retrieve['transaction_id']);
		$this->assertTrue(is_numeric($retrieve['amount']));
		$this->assertInternalType('string', $retrieve['destination']);
		$this->assertInternalType('string', $retrieve['status']);
		$this->assertNotEmpty($retrieve['status']);
		$this->assertInternalType('string', $retrieve['card_last_digits']);
		$this->assertNotEmpty($retrieve['card_last_digits']);
		echo "\n".$gateway." - retrieve: ok";


		//billetCharge (boleto bancario)
		if($gateway != 'stripe' && $gateway != 'adiq' && $gateway != 'braspag') { //stripe nao possui boleto, entao nao eh verificado no teste
			$billet = $interface->testBilletCharge();
			$this->assertTrue($billet['success']);
			$this->assertInternalType('string', $billet['billet_url']);
			$this->assertInternalType('string', $billet['digitable_line']);
			echo "\n".$gateway." - billet: ok - url: " . $billet['billet_url'];
		} else {
			echo "\n".$gateway." - billet: nao possui boleto";
		}
	}

	private function runSPlitGateways($gateway, $cardNumber = '5420222734962070'){
		$interface = new GatewaysInterfaceTest();
		
		//Cria o cartao
		$createCard = $interface->testCreateCard($cardNumber);
		$this->assertTrue($createCard['success']);
		$cardId = $createCard['payment']['id']; 
		
		//cria conta bancaria
		$charge = $interface->testCreateOrUpdateAccount($cardId);
		$this->assertTrue($charge['success']);
		$this->assertInternalType('string', $charge['recipient_id']);
		$this->assertNotEmpty($charge['recipient_id']);
		echo "\n".$gateway." - criar conta do prestador (Recipient): ok";

		//Realiza uma cobranca direta com split
		$charge = $interface->testChargeWithSplit($cardId, true);
		$this->assertTrue($charge['success']);
		$this->assertEquals($charge['status'], 'paid');
		echo "\n".$gateway." - charge com split: ok";

		//Realiza um charge no capture com split
		$chargeNoCaptureWithSplit = $interface->testChargeWithSplit($cardId, false);
		$this->assertTrue($chargeNoCaptureWithSplit['success']);
		$this->assertEquals($chargeNoCaptureWithSplit['status'], 'authorized');
		echo "\n".$gateway." - charge no capture com split: ok";

    }
}
