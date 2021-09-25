<?php

namespace Codificar\PaymentGateways\Libs;
use Carbon\Carbon;

use Codificar\PaymentGateways\Libs\JunoApi;

use ApiErrors;
use Exception;

//Models do sistema
use Payment;
use Provider;
use Transaction;
use User;
use LedgerBankAccount;
use Settings;

Class JunoLib implements IPayment
{

    public function chargeWithSplit(Payment $payment, Provider $provider, $totalAmount, $providerAmount, $description, $capture = true, User $user = null){
        \Log::error('chage_split_not_implemented');

        return array(
            "success" 			=> false ,
            "type" 				=> 'api_capture_error' ,
            "code" 				=> 'api_capture_error',
            "message" 			=> 'split_not_implementd',
            "transaction_id" 	=> ''
        );
    }
    
    public function charge(Payment $payment, $amount, $description, $capture = true, User $user = null)
    {
        try {
            $juno = new JunoApi();
            $response = $juno->charge($payment, $amount, $description, $capture);
            \Log::debug("response aqui: ");
            \Log::debug(print_r($response, true));
            if($response && $response->payments && $response->payments[0] && $response->payments[0]->id) {
                return array (
                    'success' => true,
                    'captured' => $capture,
                    'paid' => $capture ? true : false,
                    'status' => $capture ? 'paid' : 'authorized',
                    'transaction_id' => strval($response->payments[0]->id)
                );
            } else {
                return array(
                    "success" 					=> false ,
                    "type" 						=> 'api_charge_error' ,
                    "code" 						=> 'api_charge_error',
                    "message" 					=> "paymentError",
                    "transaction_id"			=> ''
                );
            }
        } catch (Exception $th) {
			\Log::error('Error juno charge');
			return array(
                "success" 					=> false ,
				"type" 						=> 'api_charge_error' ,
				"code" 						=> $th->getMessage(),
				"message" 					=> "paymentError",
				"transaction_id"			=> ''
            );
		}
       
    }

    /**
	 * Função para gerar boletos de pagamentos
	 * @param int $amount valor do boleto
	 * @param User/Provider $client instância do usuário ou prestador
	 * @param string $postbackUrl url para receber notificações do status do pagamento
	 * @param string $billetExpirationDate data de expiração do boleto
	 * @param string $billetInstructions descrição no boleto
	 * @return array
	 */
	public function billetCharge($amount, $client, $postbackUrl, $billetExpirationDate, $billetInstructions = "")
    {
        try {
            $juno = new JunoApi();
            $response = $juno->billetCharge($client, $amount, $billetInstructions,  $billetExpirationDate);
            \Log::debug("response aqui: ");
            \Log::debug(print_r($response, true));
            if($response) {
                return array (
                    'success' => true,
                    // 'captured' => true,
                    // 'paid' => ($pagarMeTransaction->status == self::PAGARME_PAID),
                    // 'status' => $pagarMeTransaction->status,
                    // 'transaction_id' => $pagarMeTransaction->id,
                    // 'billet_url' => $pagarMeTransaction->boleto_url,
                    // 'digitable_line' => $pagarMeTransaction->boleto_barcode,
                    // 'billet_expiration_date' => $pagarMeTransaction->boleto_expiration_date
                );
            } else {
                return array(
                    "success" 				=> false ,
                    "type" 					=> 'api_billet_charge_error',
                    "code" 					=> 'api_billet_charge_error',
                    "message" 				=> 'api_billet_charge_error',
                    "transaction_id"		=> ''
                );
            }
        } catch (Exception $th) {
            \Log::debug("next error");
            \Log::error($th->getMessage());
            \Log::debug("finish error");
			return array(
                "success" 				=> false ,
                "type" 					=> 'api_billet_charge_error',
                "code" 					=> 'api_billet_charge_error',
                "message" 				=> 'api_billet_charge_error',
                "transaction_id"		=> ''
            );
		}
    }

    /**
	 * Trata o postback retornado pelo gateway
	 */
	public function billetVerify ($request, $transaction_id = null)
	{
        // #todo
	}

    public function captureWithSplit(Transaction $transaction, Provider $provider, $totalAmount, $providerAmount, Payment $payment = null)
    {
        \Log::error('chage_split_not_implemented');

        return array(
            "success" 			=> false ,
            "type" 				=> 'api_capture_error' ,
            "code" 				=> 'api_capture_error',
            "message" 			=> 'split_not_implementd',
            "transaction_id" 	=> ''
        );
    }
    
    public function capture(Transaction $transaction, $amount, Payment $payment = null) {
        try {
            //valor a ser capturado nao pode ser maior que valor pre-autorizado. A responsabilidade de entrar com o valor certo e o projeto que utiliza essa biblioteca
            if($amount > $transaction->gross_value) {
                $amount = $transaction->gross_value;
            }
            
            $juno = new JunoApi();
            $response = $juno->capturePaymentCard($transaction->gateway_transaction_id, $amount);
            if($response) {
                return array (
                    'success' => true,
                    'status' => 'paid',
                    'captured' => true,
                    'paid' => true,
                    'transaction_id' => strval($transaction->gateway_transaction_id)
                );
            } else {
                return array(
                    "success" 					=> false ,
                    "type" 						=> 'api_capture_error',
                    "code" 						=> 'api_capture_error',
                    "message" 					=> 'api_capture_error',
                    "transaction_id"			=> $transaction->gateway_transaction_id
                );	
            }
        } catch (Exception $th) {
            \Log::error('Error juno capture');
            return array(
                "success" 					=> false ,
                "type" 						=> 'api_capture_error',
                "code" 						=> 'api_capture_error',
                "message" 					=> 'api_capture_error',
                "transaction_id"			=> $transaction->gateway_transaction_id
            );
        }
        
    }
   
    public function refundWithSplit(Transaction $transaction, Payment $payment)
    {
        \Log::error('capture_not_implemented');

        return array(
            "success" 			=> false ,
            "type" 				=> 'api_refund_split_error' ,
            "code" 				=> 'api_refund_split_error',
            "message" 			=> 'refund_split_not_implementd',
            "transaction_id" 	=> ''
        );
    }
  
    public function refund(Transaction $transaction, Payment $payment)
    {
        
		try {
            $juno = new JunoApi();
            $refundStatus = $juno->refundCard($transaction->gateway_transaction_id);

			if($refundStatus) {
				return array(
					"success" 			=> true ,
					"status" 			=> 'refunded',
					"transaction_id" 	=> $transaction->gateway_transaction_id,
				);
			} else {
                return array(
                    "success" 			=> false ,
                    "type" 				=> 'api_refund_error' ,
                    "code" 				=> 'api_refund_error',
                    "message" 			=> 'api_refund_error',
                    "transaction_id" 	=> $transaction->gateway_transaction_id
                );
            }
		} catch (Exception $th) {
			\Log::error('Error Pagarapido refund 2 ');
			return array(
                "success" 					=> false ,
				"type" 						=> 'api_refund_error' ,
				"code" 						=> $th->getMessage(),
				"message" 					=> "api_refund_error",
				"transaction_id"			=> $transaction->gateway_transaction_id
            );
            
		}
    }
      
    public function retrieve(Transaction $transaction, Payment $payment = null)
    {
		return array(
			'success' => true,
			'transaction_id' => strval($transaction->gateway_transaction_id),
			'amount' => $transaction->gross_value,
			'destination' => '',
			'status' => $transaction->status,
			'card_last_digits' => $payment ? $payment->last_four : '',
		);
    }
     
    public function createCard(Payment $payment, User $user = null)
    {
        $cardNumber = $payment->getCardNumber();

		$result = array(
			'success'		=>	true,
			'customer_id'	=>	'',
			'last_four'		=>	substr($cardNumber, -4),
			'card_type'		=>	strtolower(detectCardType($cardNumber)),
            'card_token'	=>	'',
            'token'	        =>	'',
            'gateway'       => 'juno'
		);

		return $result;
    }

    public static function createCardToken($creditCardHash)
    {
        try {
            $juno = new JunoApi();
            $response = $juno->createCardToken($creditCardHash);
            if($response && $response->creditCardId) {
                return $response->creditCardId;
            } else {
                return null;
            }
        } catch (Exception $th) {
            return null;
        }
        
    }
    
    public function deleteCard(Payment $payment, User $user = null){
        
    }    

   
    public function createOrUpdateAccount(LedgerBankAccount $ledgerBankAccount)
    {
        \Log::error('split_not_implemented');

        return array(
            "success" 			=> false ,
            "type" 				=> 'api_split_error' ,
            "code" 				=> 'api_split_error',
            "message" 			=> 'split_not_implementd',
            "transaction_id" 	=> ''
        );
    }

    /**
     *  Return a gateway fee
     * 
     * @return Decimal
     */        
    public function getGatewayFee()
    {
        return 0;
    }

    /**
     *  Return a gateway tax
     * 
     * @return Decimal
     */      
    public function getGatewayTax()
    {
        return 0;
    }

    public function getNextCompensationDate(){
		$carbon = Carbon::now();
		$compDays = Settings::findByKey('compensate_provider_days');
		$addDays = ($compDays || (string)$compDays == '0') ? (int)$compDays : 31;
		$carbon->addDays($addDays);
		
		return $carbon;
	}
  
    public function checkAutoTransferProvider()
    {
        return false;
    }

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

}