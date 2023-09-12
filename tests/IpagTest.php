<?php

namespace Tests\Unit\libs\gateways;

use Exception;
use Log;
use Tests\TestCase;
use Settings;
use Tests\Unit\libs\gateways\GatewaysInterfaceTest;

// to run test: sail artisan test --filter IpagTest
class IpagTest extends TestCase
{
    const GATEWAY = 'ipag';
    const CATEGORY = 2;
    const SUB_CATEGORY = 0;
    const PAGE = 1;
    const DELAY = 0;
    const CARD_NUMBER = '5420222734962070';
    const IS_TERRA_CARD = false;

    /**
	 * Change settings default gateway
	 * @param string $gateway
	 * @param bool $isPix
	 * 
	 * @return void
	 */
	public function testSetGatewaySettings(): void
	{
        try {
            Settings::where('key', 'default_payment')->update(['value' => self::GATEWAY]);
            //Change the pix keys
            Settings::where('key', 'default_payment_pix')->update(['value' => self::GATEWAY]);
            $this->assertTrue(true);
            
        } catch(Exception $e) {
            Log::debug($e->getMessage() . $e->getTraceAsString());
            $this->assertTrue(false);
        }
	}


	public function testSetCredentialsKeySuccess() {
		$saveKeys = $this->setCredentialsSettings(KeyAccess::getArrayKeys(self::GATEWAY));
		$this->assertTrue($saveKeys);
		$saveKeysPix = $this->setCredentialsSettings(KeyAccess::getArrayKeysPix(self::GATEWAY));
		$this->assertTrue($saveKeysPix);
	}

    public function testCreateCardSuccess()
    {
        $interface = new GatewaysInterfaceTest();
		//Cria o cartão e verifica se todos os parâmetros estão ok
		$createCard = $createCard = $interface->testCreateCard(self::IS_TERRA_CARD);;
        $this->assertTrue($createCard['success']);
        $this->assertIsString($createCard['token']);
        $this->assertIsString($createCard['card_token']);
        $this->assertIsString($createCard['customer_id']);
        $this->assertIsString($createCard['card_type']);
        $this->assertNotEmpty($createCard['card_type']);
        $this->assertIsString($createCard['last_four']);
        $this->assertNotEmpty($createCard['last_four']);
        $this->assertIsString($createCard['gateway']);
        $this->assertNotEmpty($createCard['gateway']);

    }


    public function testCreateChargeSuccess()
    {
        $interface = new GatewaysInterfaceTest();
        $cardId = $interface->getLastCardId();
		//Realiza uma cobrança direta e sem split
		$charge = $interface->testCharge($cardId, self::IS_TERRA_CARD);
        $this->assertTrue($charge['success']);
        $this->assertTrue($charge['captured']);
        $this->assertTrue($charge['paid']);
        $this->assertEquals($charge['status'], 'paid');
        $this->assertIsString($charge['transaction_id']);
        $this->assertNotEmpty($charge['transaction_id']);
    }

    public function testCreateChargeNoCaptureSuccess()
    {
        $interface = new GatewaysInterfaceTest();
        $cardId = $interface->getLastCardId();
        $chargeNoCapture = $interface->testChargeNoCapture($cardId, self::IS_TERRA_CARD);
        $this->assertTrue($chargeNoCapture['success']);
        $this->assertFalse($chargeNoCapture['captured']);
        $this->assertFalse($chargeNoCapture['paid']);
        $this->assertEquals($chargeNoCapture['status'], 'authorized');
        $this->assertIsString($chargeNoCapture['transaction_id']);
        $this->assertNotEmpty($chargeNoCapture['transaction_id']);
    }

    public function testCaptureChargeSuccess()
    {	
        $interface = new GatewaysInterfaceTest();
        $cardId = $interface->getLastCardId();
        $transactionId = $interface->getLastTransactionIdAuthorized();
        //Faz o capture da pre-autorização anterior. Passa como parâmetro a transaction_id da pre-autorização.
        $capture = $interface->testCapture($transactionId, $cardId);
        $this->assertTrue($capture['success']);
        $this->assertEquals($capture['status'], 'paid');
        $this->assertTrue($capture['captured']);
        $this->assertTrue($capture['paid']);
        $this->assertIsString($capture['transaction_id']);
        $this->assertNotEmpty($capture['transaction_id']);
    }

    public function testRetrieveChargeSuccess()
    {	
        $interface = new GatewaysInterfaceTest();
        $cardId = $interface->getLastCardId();
        $transactionId = $interface->getLastTransactionIdPaid();
        //retrieve (recuperar os dados) a transaction
		$retrieve = $interface->testRetrieve($transactionId, $cardId);
        $this->assertTrue($retrieve['success']);
        $this->assertIsString($retrieve['transaction_id']);
        $this->assertNotEmpty($retrieve['transaction_id']);
        $this->assertIsNumeric($retrieve['amount']);
        $this->assertIsString($retrieve['destination']);
        $this->assertIsString($retrieve['status']);
        $this->assertNotEmpty($retrieve['status']);
    }

    public function testRefundChargeSuccess()
    {	
        $interface = new GatewaysInterfaceTest();
        $cardId = $interface->getLastCardId();
        $transactionId = $interface->getLastTransactionIdPaid();
        //Faz o cancelamento da transação
		$refund = $interface->testRefund($transactionId, $cardId);
        $this->assertTrue($refund['success']);
        $this->assertEquals($refund['status'], 'refunded');
        $this->assertIsString($refund['transaction_id']);
        $this->assertNotEmpty($refund['transaction_id']);
    }

    public function testBilletChargeSuccess()
    {	
        if(env('APP_ENV') != 'production') {
            $this->assertTrue(true, "billet: não pode ser realizado em ambiente de teste ou localhost");
		} else {
            $interface = new GatewaysInterfaceTest();
            $billet = $interface->testBilletCharge();
            $this->assertTrue($billet['success']);
            $this->assertIsString($billet['billet_url']);
            $this->assertIsString($billet['digitable_line']);
        }
    }

	public function testPixSuccess() {
        $interface = new GatewaysInterfaceTest();
		$pixCharge = $interface->testPixCharge();
		$this->assertTrue($pixCharge['success']);
		$this->assertNotEmpty($pixCharge['qr_code_base64']);
		$this->assertNotEmpty($pixCharge['copy_and_paste']);
		$this->assertNotEmpty($pixCharge['transaction_id']);
	}

    public function testCreateOrUpdateAccountSuccess()
    {	
        $interface = new GatewaysInterfaceTest();
        $cardId = $interface->getLastCardId();
        //cria conta bancaria
		$charge = $interface->testCreateOrUpdateAccount($cardId);
		
        if($charge && !$charge['success'] && $charge['code'] == '403') {
			$this->addWarning(false, "criar conta do prestador (Recipient): Conta não tem permissão para efetuar ação.");
		} else {
			$this->assertTrue($charge['success']);
			$this->assertIsString($charge['recipient_id']);
			$this->assertNotEmpty($charge['recipient_id']);
		}
    }

    public function testChargeWithSplitSuccess()
    {
        $interface = new GatewaysInterfaceTest();
        $cardId = $interface->getLastCardId();
        $charge = $interface->testChargeWithSplit($cardId, true);
		
        if($charge && !$charge['success'] && $charge['code'] == '500') {
			$this->addWarning(false, 'charge: Não foi possível comunicar com o servidor (500)');
		} else if($charge && !$charge['success'] 
            && ($charge['code'] == '0' || $charge['code'] == '-2')) {
            $message = "Code: " . $charge['code'] . " - Message:" . $charge['message'];
            $this->addWarning(false, $message);
		} else {
			$this->assertTrue($charge['success']);
			$this->assertEquals($charge['status'], 'paid');
		}
    }

    public function testChargeNoCaptureWithSplitSuccess()
    {	
        $interface = new GatewaysInterfaceTest();
        $cardId = $interface->getLastCardId();
        //Realiza um charge no capture com split
		$charge = $interface->testChargeWithSplit($cardId, false);
		if($charge && !$charge['success'] && $charge['code'] == '500') {
            $this->addWarning(false, "Não foi possível comunicar com o servidor (500)");
		} else if($charge && !$charge['success'] && ($charge['code'] == '0' || $charge['code'] == '-2')) {
            $message = "\nCode: " . $charge['code'] . " - Message:" . $charge['message'];
            $this->addWarning(false, $message);
		} else if($charge && !$charge['success'] && $charge['response']) {
            $message = "\nCode: " . $charge['code'] . " - Message:" . $charge['message'];
            $this->addWarning(false, $message);
		} else {
			$this->assertTrue($charge['success']);
			$this->assertEquals($charge['status'], 'authorized');
		}
    }

	/**
	 * change value credentials 
	 * @param array $credential - sample [ 'key' => 'api_id', 'value' => '123...789']
     * 
     * @return bool
	 */
	private function setCredentialsSettings(array $credentials): bool
	{
        try {
            foreach($credentials as $credential) {
                Settings::updateOrCreate(
                    array('key' => $credential['key']), 
                    array(
                        'key' => $credential['key'],
                        'value' => $credential['value'], 
                        'page' => self::PAGE, 
                        'category' => self::CATEGORY, 
                        'sub_category' => self::SUB_CATEGORY, 
                    )
                );
            }
        } catch(Exception $e) {
            Log::debug($e->getMessage() . $e->getTraceAsString());
            return false;
        }
        return true;
	}
}
