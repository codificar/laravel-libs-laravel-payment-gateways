<?php

namespace Tests\Unit\libs\gateways;

use Exception;
use Log;
use Tests\TestCase;
use Settings;
use Tests\Unit\libs\gateways\GatewaysInterfaceTest;

// to run test: sail artisan test --filter BancardTest
class BancardTest extends TestCase
{
    const GATEWAY = 'bancard';
    const CATEGORY = 2;
    const SUB_CATEGORY = 0;
    const PAGE = 1;
    const DELAY = 0;
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
            $this->assertTrue(true);
            
        } catch(Exception $e) {
            Log::debug($e->getMessage() . $e->getTraceAsString());
            $this->assertTrue(false);
        }
	}


	public function testSetCredentialsKeySuccess() {
		$saveKeys = $this->setCredentialsSettings(KeyAccess::getArrayKeys(self::GATEWAY));
		$this->assertTrue($saveKeys);
	}

    public function testCreateCardSuccess()
    {

        $interface = new GatewaysInterfaceTest();
        if(self::DELAY)
			sleep(self::DELAY);
		//Cria o cartão e verifica se todos os parâmetros estão ok
		$createCard = $interface->testCreateCard(self::IS_TERRA_CARD);
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
        
        if(self::DELAY) {
			sleep(self::DELAY);
        }

		//Realiza uma cobrança direta e sem split
		$charge = $interface->testCharge($cardId, self::IS_TERRA_CARD);
        if($charge && !$charge['success']) {
			$this->addWarning( "Error: " . $charge['type'] . " - Message: " . $charge['message']);
		} else {
            $this->assertTrue($charge['success']);
            $this->assertTrue($charge['captured']);
            $this->assertTrue($charge['paid']);
            $this->assertEquals($charge['status'], 'paid');
            $this->assertIsString($charge['transaction_id']);
            $this->assertNotEmpty($charge['transaction_id']);
        }
    }

    public function testCreateChargeNoCaptureSuccess()
    {
        $interface = new GatewaysInterfaceTest();
        $cardId = $interface->getLastCardId();
        
        if(self::DELAY) {
			sleep(self::DELAY);
        }

        $chargeNoCapture = $interface->testChargeNoCapture($cardId, self::IS_TERRA_CARD);
        if($chargeNoCapture && !$chargeNoCapture['success']) {
			$this->addWarning( "Error: " . $chargeNoCapture['type'] . " - Message: " . $chargeNoCapture['message']);
		} else {
            $this->assertTrue($chargeNoCapture['success']);
            $this->assertFalse($chargeNoCapture['captured']);
            $this->assertFalse($chargeNoCapture['paid']);
            $this->assertEquals($chargeNoCapture['status'], 'authorized');
            $this->assertIsString($chargeNoCapture['transaction_id']);
            $this->assertNotEmpty($chargeNoCapture['transaction_id']);
        }
    }

    public function testRetrieveChargeSuccess()
    {	
        $interface = new GatewaysInterfaceTest();
        $cardId = $interface->getLastCardId();
        $transactionId = $interface->getLastTransactionIdPaid();
        
        if(self::DELAY) {
			sleep(self::DELAY);
        }

        //retrieve (recuperar os dados) a transaction
		$retrieve = $interface->testRetrieve($transactionId, $cardId);
        if($retrieve && !$retrieve['success']) {
			$this->addWarning( "Error: " . $retrieve['type'] . " - Message: " . $retrieve['message']);
		} else {
            $this->assertTrue($retrieve['success']);
            $this->assertIsString($retrieve['transaction_id']);
            $this->assertNotEmpty($retrieve['transaction_id']);
            $this->assertIsNumeric($retrieve['amount']);
            $this->assertIsString($retrieve['destination']);
            $this->assertIsString($retrieve['status']);
            $this->assertNotEmpty($retrieve['status']);
        }
    }

    public function testRefundChargeSuccess()
    {	
        $interface = new GatewaysInterfaceTest();
        $cardId = $interface->getLastCardId();
        $transactionId = $interface->getLastTransactionIdPaid();
        
        if(self::DELAY) {
			sleep(self::DELAY);
        }
        
        //Faz o cancelamento da transação
		$refund = $interface->testRefund($transactionId, $cardId);
        if($refund && !$refund['success']) {
			$this->addWarning( "Error: " . $refund['error'] . " - Message: " . $refund['message']);
		} else {
            $this->assertTrue($refund['success']);
            $this->assertEquals($refund['status'], 'refunded');
            $this->assertIsString($refund['transaction_id']);
            $this->assertNotEmpty($refund['transaction_id']);
        }
    }


    public function testDeleteCardSuccess()
    {

        $interface = new GatewaysInterfaceTest();
        if(self::DELAY)
			sleep(self::DELAY);
		//Cria o cartão e verifica se todos os parâmetros estão ok
		$createCard = $interface->testDeleteCard();
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
