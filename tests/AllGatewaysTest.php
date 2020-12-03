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
