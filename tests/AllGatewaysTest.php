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
		//Update the gateway selected
		Settings::where('key', 'default_payment')->update(['value' => 'pagarme']);

		//Change the keys
		Settings::where('key', 'pagarme_encryption_key')->update(['value' => 'ek_test_pHEXVtJxrD2z7BR2k3vABIcQhg9Lob']);
		Settings::where('key', 'pagarme_recipient_id')->update(['value' => 're_ckfcykeja0c5psw6eq7ohg6wx']);
		Settings::where('key', 'pagarme_api_key')->update(['value' => 'ak_test_PlTjytFjSz5RLwcUK9TFssfmKMac8y']);

		$this->runInterfaceGateways();
	}

	public function testCielo() {
		//Update the gateway selected
		Settings::where('key', 'default_payment')->update(['value' => 'cielo']);

		//Change the keys
		Settings::where('key', 'cielo_merchant_key')->update(['value' => 'BKUWHFVDBQSOTKINJCNPMCTEFZROQRFBXSHRYIPJ']);
		Settings::where('key', 'cielo_merchant_id')->update(['value' => '851c6271-df49-4ee6-b212-79ea22ae839f']);

		$this->runInterfaceGateways();
	}

    private function runInterfaceGateways(){
		$interface = new GatewaysInterfaceTest();
		
		//Cria o cartao
		$createCard = $interface->testCreateCard();
		$this->assertTrue($createCard);
		echo "\nPagarme - Criar cartao: ok";

		//Realiza uma cobranca direta e sem split
		$charge = $interface->testCharge();
		$this->assertTrue($charge['success']);
		$this->assertEquals($charge['status'], 'paid');
		echo "\nPagarme - charge sem split: ok";

		//Realiza uma pre-autorizacao (chargeNoCapture) sem split
		$chargeNoCapture = $interface->testChargeNoCapture();
		$this->assertTrue($chargeNoCapture['success']);
		$this->assertEquals($chargeNoCapture['status'], 'authorized');
		echo "\nPagarme - chargeNoCapture sem split: ok";

		//Faz o capture da pre-autorizacao anterior. Passa como parametro a transaction_id da pre-autorizacao.
		$capture = $interface->testCapture($chargeNoCapture['transaction_id']);
		$this->assertTrue($capture['success']);
		$this->assertEquals($capture['status'], 'paid');
		echo "\nPagarme - capture sem split: ok";
    }
}
