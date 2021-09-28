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
            if(
                $response && $response->payments && $response->payments[0] && $response->payments[0]->id &&
                ($response->payments[0]->status == 'CONFIRMED' || $response->payments[0]->status == 'AUTHORIZED')
            ) {
                return array (
                    'success' => true,
                    'captured' => $capture,
                    'paid' => $capture ? true : false,
                    'status' => $capture ? 'paid' : 'authorized',
                    //transaction_id tem dois ids: da cobranca (charge_id) e do pagamento de fato (pay_id). No gateway da juno, precisamos desses dois transactions ids, por isso foi serializado
                    'transaction_id' => serialize(array(
                        'charge_id' => $response->payments[0]->chargeId,
                        'pay_id' => $response->payments[0]->id
                    ))
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
            if($response && $response->_embedded && $response->_embedded->charges[0] && $response->_embedded->charges[0]->code) {
                return array (
                    'success' => true,
                    'captured' => true,
                    'paid' => false,
                    'status' => 'waiting_payment',
                    'transaction_id' => serialize(array(
                        'charge_id' => $response->_embedded->charges[0]->id,
                        'code' => $response->_embedded->charges[0]->code
                    )),
                    'billet_url' => $response->_embedded->charges[0]->link,
                    'digitable_line' => $response->_embedded->charges[0]->billetDetails->barcodeNumber,
                    'billet_expiration_date' => $billetExpirationDate
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
            \Log::error($th->getMessage());
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
        if(isset($request->chargeCode) && $request->chargeCode) {
            //pega as possiveis transacoes que tem o charge code da juno.
            $possibleTransactions = Transaction::where('gateway_transaction_id', 'like', '%' . $request->chargeCode . '%')->get();
            //Verifica se pegou a transacao correta, fazendo o unserialize no array e verificando o code_id da juno
            foreach($possibleTransactions as $transaction) {
                //verifica se essa transacao e uma transacao do tipo boleto 
                if($transaction->billet_link) {
                    $transactionIds = unserialize($transaction->gateway_transaction_id);
                    if($transactionIds['code'] == $request->chargeCode) {
                        $retrieve = $this->retrieve($transaction);
                        if($retrieve['success'] && $retrieve['status']) {
                            return [
                                'success' => true,
                                'status' => $retrieve['status'],
                                'transaction_id' => $transaction->id
                            ];
                        }
                    }
                }
            }
            return [
                'success' => false,
                'status' => '',
                'transaction_id' => ''
            ];
        } else {
            return [
                'success' => false,
                'status' => '',
                'transaction_id' => ''
            ];
        }
		
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
            $transactionIds = unserialize($transaction->gateway_transaction_id);

            $response = $juno->capturePaymentCard($transactionIds['pay_id'], $amount);
            if($response) {
                return array (
                    'success' => true,
                    'status' => 'paid',
                    'captured' => true,
                    'paid' => true,
                    'transaction_id' => $transaction->gateway_transaction_id
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
            $transactionIds = unserialize($transaction->gateway_transaction_id);
            $payIsConfirmed = $transaction->status == 'paid' ? true : false;
            $refundStatus = $juno->refundCard($transactionIds['charge_id'], $transactionIds['pay_id'], $payIsConfirmed);
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
			\Log::error('Error juno refund 2');
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

        try {
            $juno = new JunoApi();
            $transactionIds = unserialize($transaction->gateway_transaction_id);
            $retrieve = $juno->retrieve($transactionIds['charge_id']);
            
            //se o pagamento foi feito por boleto
            if($retrieve && isset($retrieve->billetDetails) && isset($retrieve->billetDetails) && isset($retrieve->billetDetails->barcodeNumber) && $retrieve->billetDetails->barcodeNumber) {
                switch ($retrieve->status) {
                    case 'ACTIVE':
                        $status = 'waiting_payment';
                        break;
                    case 'CANCELLED':
                        $status = 'refunded';
                        break;
                    case 'MANUAL_RECONCILIATION':
                        $status = 'refunded';
                        break;
                    case 'FAILED':
                        $status = 'error';
                        break;
                    case 'PAID':
                        $status = 'paid';
                        break;
                    default:
                        $status = 'error';
                }
                return array(
                    'success' => true,
                    'transaction_id' => $transaction->gateway_transaction_id,
                    'amount' => $retrieve->amount,
                    'destination' => '',
                    'status' => $status,
                    'card_last_digits' => $payment ? $payment->last_four : '',
                );
            }

            //se o pagamento foi feito no cartao
			else if($retrieve && isset($retrieve->payments) && isset($retrieve->payments[0]) && isset($retrieve->payments[0]->status) && $retrieve->payments[0]->status) {
                if($retrieve->status == 'CANCELLED') {
                    $status = 'refunded';
                } else {
                    switch ($retrieve->payments[0]->status) {
                        case 'DECLINED':
                            $status = 'refused';
                            break;
                        case 'FAILED':
                            $status = 'refused';
                            break;
                        case 'NOT_AUTHORIZED':
                            $status = 'refused';
                            break;
                        case 'AUTHORIZED':
                            $status = 'authorized';
                            break;
                        case 'CONFIRMED':
                            $status = 'paid';
                            break;
                        case 'CUSTOMER_PAID_BACK':
                            $status = 'refunded';
                            break;
                        case 'BANK_PAID_BACK':
                            $status = 'refunded';
                            break;
                        case 'PARTIALLY_REFUNDED':
                            $status = 'refunded';
                            break;
                        default:
                            $status = 'error';
                    }
                }
				return array(
                    'success' => true,
                    'transaction_id' => $transaction->gateway_transaction_id,
                    'amount' => $retrieve->amount,
                    'destination' => '',
                    'status' => $status,
                    'card_last_digits' => $payment ? $payment->last_four : '',
                );
			} else {
                \Log::error('Error juno retrieve 2');
                return array(
                    "success" 				=> false ,
                    "type" 					=> 'api_retrieve_error' ,
                    "code" 					=> '',
                    "message" 				=> 'api_retrieve_error '
                );
            }
		} catch (Exception $th) {
			\Log::error('Error juno retrieve 2');
            return array(
                "success" 				=> false ,
                "type" 					=> 'api_retrieve_error' ,
                "code" 					=> '',
                "message" 				=> $th->getMessage()
            );
		}
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