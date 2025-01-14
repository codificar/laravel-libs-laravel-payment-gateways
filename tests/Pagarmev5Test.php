<?php

namespace Tests\Unit\libs\gateways;

use Exception;
use Log;
use Tests\TestCase;
use Settings;
use Tests\Unit\libs\gateways\GatewaysInterfaceTest;

// to run test: sail artisan test --filter Pagarmev5Test
class Pagarmev5Test extends TestCase
{
    const GATEWAY = 'pagarme';
    const CATEGORY = 6;
    const SUB_CATEGORY = 0;
    const PAGE = 1;
    const DELAY = 5;
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
        $credentials = KeyAccess::getArrayKeys(self::GATEWAY);
        $saveKeys = $this->setCredentialsSettings($credentials);
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
        
        if(self::DELAY) {
			sleep(self::DELAY);
        }

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
        
        if(self::DELAY) {
			sleep(self::DELAY);
        }

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
        
        if(self::DELAY) {
			sleep(self::DELAY);
        }

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
        
        if(self::DELAY) {
			sleep(self::DELAY);
        }

        //Faz o cancelamento da transação
		$refund = $interface->testRefund($transactionId, $cardId);
        $this->assertIsArray($refund);
        if(isset($refund)) {
            $this->assertTrue($refund['success']);
            $this->assertEquals($refund['status'], 'refunded');
            $this->assertIsString($refund['transaction_id']);
            $this->assertNotEmpty($refund['transaction_id']);
        }
    }

    public function testBilletChargeSuccess()
    {	
        if(env('APP_ENV') != 'production') {
            $this->addWarning("billet: não pode ser realizado em ambiente de teste ou localhost");
		} else {
            $interface = new GatewaysInterfaceTest();
            
            if(self::DELAY) {
			    sleep(self::DELAY);
            }

            $billet = $interface->testBilletCharge();
            $this->assertTrue($billet['success']);
            $this->assertIsString($billet['billet_url']);
            $this->assertIsString($billet['digitable_line']);
        }
    }

    public function testCreateOrUpdateAccountSuccess()
    {	
        $interface = new GatewaysInterfaceTest();
        $cardId = $interface->getLastCardId();

        if(self::DELAY) {
			sleep(self::DELAY);
        }

        //cria conta bancaria
		$charge = $interface->testCreateOrUpdateAccount($cardId);
        $code = isset($charge['code']) ? $charge['code'] : null;
		
        if($charge && !$charge['success'] && $code && $code == '403') {
			$this->addWarning("criar conta do prestador (Recipient): Conta não tem permissão para efetuar ação.");
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

        if(self::DELAY) {
			sleep(self::DELAY);
        }

        $charge = $interface->testChargeWithSplit($cardId, true);
		$code = isset($charge['code']) ? $charge['code'] : null;
		$message = isset($charge['message']) ? $charge['message'] : '';

        if($charge && !$charge['success'] && $code && $code == '500') {
			$this->addWarning('charge: Não foi possível comunicar com o servidor (500)');
		} else if($charge && !$charge['success'] && $code
            && ($code == '0' || $code == '-2')) {
            $message = "Code: $code - Message: $message";
            $this->addWarning($message);
		} else {
			$this->assertTrue($charge['success']);
			$this->assertEquals($charge['status'], 'paid');
		}
    }

    public function testChargeNoCaptureWithSplitSuccess()
    {	
        $interface = new GatewaysInterfaceTest();
        $cardId = $interface->getLastCardId();

        if(self::DELAY) {
			sleep(self::DELAY);
        }

        //Realiza um charge no capture com split
		$charge = $interface->testChargeWithSplit($cardId, false);
        $code = isset($charge['code']) ? $charge['code'] : null;
		$message = isset($charge['message']) ? $charge['message'] : '';
        
		if($charge && !$charge['success'] && $code && $code == '500') {
            $this->addWarning("Não foi possível comunicar com o servidor (500)");
		} else if($charge && !$charge['success'] && $code && 
            ($code == '0' || $code == '-2')) {
            $message = "\nCode: $code - Message: $message";
            $this->addWarning($message);
		} else if($charge && !$charge['success'] && $code && $charge['response']) {
            $message = "\nCode: $code - Message: $message";
            $this->addWarning($message);
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
            print_r("** Pagarme v5 Teste - Chaves utilizadas: \n");
            foreach($credentials as $credential) {
                print_r($credential['key'] . ": " . $credential['value'] . " \n");
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
