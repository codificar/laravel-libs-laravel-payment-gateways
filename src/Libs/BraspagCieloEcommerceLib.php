<?php

namespace Codificar\PaymentGateways\Libs;
use Carbon\Carbon;

use Codificar\PaymentGateways\Libs\BraspagCieloEcommerceApi;

use ApiErrors;
//models do sistema
use Payment;
use Provider;
use Transaction;
use User;
use LedgerBankAccount;
use Settings;

Class BraspagCieloEcommerceLib implements IPayment
{
    private $api;

    const CHARGE_SUCCESS = 1;
    const CAPTURE_SUCCESS = 2;
    const REFUND_SUCCESS = 10;
    const AUTO_TRANSFER_PROVIDER = 'auto_transfer_provider_payment';
    const WAITING_PAYMENT = 'waiting_payment';

    public function setApi()
    {
        $this->api = new BraspagCieloEcommerceApi();
    }

    /**
     * Charge a credit card with split rules
     *
     * @param Payment       $payment
     * @param Provider      $provider 
     * @param Decimal       $totalAmount        A positive decimal representing how much to charge
     * @param Decimal       $providerAmount 
     * @param String        $description        An arbitrary string which you can attach to describe a Charge object
     * @param Boolean       $capture            Whether to immediately capture the charge. When false, the charge issues an authorization (or pre-authorization), and will need to be captured later. 
     * @param User          $user               The customer that will be charged in this request
     * 
     * @return Array ['success', 'status', 'captured', 'paid', 'transaction_id']
     */    
    public function chargeWithSplit(Payment $payment, Provider $provider, $totalAmount, $providerAmount, $description, $capture = true, User $user = null)
    {
        try {
            $this->setApi();
            $response = $this->api->chargeWithSplit($payment, $provider, $totalAmount, $providerAmount, $description, $capture, true);

            $responseChargeStatus = self::getChargeStatus(true, $capture);

			if ($response->success && $response->data->Payment->Status == $responseChargeStatus) {
				$result = array (
					'success' 		    => true,
					'status' 		    => $capture ? 'paid' : 'authorized',
					'captured' 			=> $capture,
					'paid' 		        => $capture,
					'transaction_id'    => $response->data->Payment->PaymentId
				);
				return $result;
			} else {
                return array(
                    "success" 	=> false ,
                    'data' 		=> null,
                    'transaction_id'	=> '',
                    'error' 	=> array(
                        "code" 		=> ApiErrors::CARD_ERROR,
                        "messages" 	=> array(trans('creditCard.customerCreationFail'))
                    )
                );
            }
        }
        catch (Exception $th)
        {
            \Log::error($th->getMessage());
			return array(
				"success" 	=> false ,
				'data' 		=> null,
				'transaction_id'	=> '',
				'error' 	=> array(
					"code" 		=> ApiErrors::CARD_ERROR,
					"messages" 	=> array(trans('creditCard.customerCreationFail'))
				)
			);
		}
    }
    
    /**
     * Charge a credit card
     *
     * @param Payment       $payment
     * @param Decimal       $totalAmount        A positive decimal representing how much to charge
     * @param String        $description        An arbitrary string which you can attach to describe a Charge object
     * @param Boolean       $capture            Whether to immediately capture the charge. When false, the charge issues an authorization (or pre-authorization), and will need to be captured later. 
     * @param User          $user               The customer that will be charged in this request
     * 
     * @return Array ['success', 'status', 'captured', 'paid', 'transaction_id']
     */    
    public function charge(Payment $payment, $amount, $description, $capture = true, User $user = null)  
    {
        \Log::error('chage_not_implemented');

        return array(
            "success" 			=> false,
            "type" 				=> 'api_charge_error',
            "code" 				=> 'api_charge_error',
            "message" 			=> 'charge_not_implementd',
            "transaction_id" 	=> '',
            "paid"              => false
        );

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
    public function billetCharge($amount, $client, $postbackUrl = null, $billetExpirationDate, $billetInstructions)
    {
        // try {
        // $this->setApi();
        //     $response = $this->api->billetCharge($amount, $client, $postbackUrl, $billetExpirationDate, $billetInstructions);

        //     if ($response && $response->data && $response->data->Payment->Status == self::CHARGE_SUCCESS) {
        //         return array (
        //             'success' => true,
        //             'captured' => true,
        //             'paid' => false,
        //             'status' => self::WAITING_PAYMENT,
        //             'transaction_id' => $response->data->Payment->PaymentId,
        //             'billet_url' => $response->data->Payment->Url,
        //             'billet_expiration_date' => $response->data->Payment->ExpirationDate
        //         );
        //     } else {
        //         return array(
        //             "success" 				=> false ,
        //             "type" 					=> 'api_charge_error' ,
        //             "code" 					=> '',
        //             "message" 				=> '',
        //             "transaction_id"		=> ''
        //         );
        //     }
        // } catch (\Throwable $th) {
        //     \Log::error($th->getMessage());

		// 	return array(
		// 		"success" 				=> false ,
		// 		"type" 					=> 'api_charge_error' ,
		// 		"code" 					=> '',
		// 		"message" 				=> $th->getMessage(),
		// 		"transaction_id"		=> ''
		// 	);
        // }

        return array(
            "success" 				=> false ,
            "type" 					=> 'api_charge_error',
            "code" 					=> '',
            "message" 				=> 'api_charge_error',
            "transaction_id"		=> ''
        );
    }

    /**
	 * Trata o postback retornado pelo gateway
	 */
	public function billetVerify ($request, $transaction_id = null)
	{
		$postbackTransaction = $request->PaymentId;
        
		if (!$postbackTransaction)
			return [
				'success' => false,
				'status' => '',
				'transaction_id' => ''
            ];
        
        $transaction = Transaction::getTransactionByGatewayId($postbackTransaction);
        $retrieve = $this->retrieve($transaction);

		return [
			'success' => true,
			'status' => $retrieve['status'],
			'transaction_id' => $retrieve['transaction_id']
		];
	}

    /**
     * Capture the payment of an existing, uncaptured, charge with split rules
     *
     * @param Transaction   $transaction
     * @param Provider      $provider
     * @param Decimal       $totalAmount        A positive decimal representing how much to charge
     * @param Decimal       $providerAmount 
     * @param Payment       $payment               
     * 
     * @return Array ['success', 'status', 'captured', 'paid', 'transaction_id']
     */         
    public function captureWithSplit(Transaction $transaction, Provider $provider, $totalAmount, $providerAmount, Payment $payment = null)
    {
        try {
            $this->setApi();
            $response = $this->api->captureWithSplit($transaction, $provider, $totalAmount, $providerAmount);

			if ($response->success && $response->data->Status == self::CAPTURE_SUCCESS) {
				$result = array (
					'success' 		 => true,
					'captured' 		 => true,
					'paid' 			 => true,
					'status' 		 => 'paid',
					'transaction_id' => $transaction->gateway_transaction_id
				);
				return $result;
			} else {
                return array(
                    "success" 	=> false ,
                    'data' 		=> null,
                    'error' 	=> array(
                        "code" 		=> ApiErrors::CARD_ERROR,
                        "messages" 	=> array(trans('creditCard.customerCreationFail'))
                    )
                );
            }
		} catch (\Throwable $th) {
            \Log::error($th->__toString());
			
			return array(
				"success" 	=> false ,
				'data' 		=> null,
				'error' 	=> array(
					"code" 		=> ApiErrors::CARD_ERROR,
					"messages" 	=> array(trans('creditCard.customerCreationFail'))
				)
			);
		}
    }
    
    /**
     * Capture the payment of an existing, uncaptured, charge
     *
     * @param Transaction   $transaction
     * @param Decimal       $totalAmount        A positive decimal representing how much to charge
     * @param Payment       $payment     
     * 
     * @return Array ['success', 'status', 'captured', 'paid', 'transaction_id']
     */       
    public function capture(Transaction $transaction, $amount, Payment $payment = null)
    {
        \Log::error('capture_not_implemented');

        return array(
            "success" 			=> false,
            "type" 				=> 'api_capture_error',
            "code" 				=> 'api_capture_error',
            "message" 			=> 'capture_not_implementd',
            "transaction_id" 	=> '',
            "paid"              => false
        );
    }

    /**
     * Refund a charge that has previously been created with split rules
     *
     * @param Transaction   $transaction
     * @param Payment       $payment 
     * 
     * @return Array ['success', 'status', 'transaction_id']
     */       
    public function refundWithSplit(Transaction $transaction, Payment $payment)
    {
        try {
            $this->setApi();
			$response = $this->api->refund($transaction);
			
			if($response->success && $response->data->Status == self::REFUND_SUCCESS)
            {
                $result = array(
                    "success" 					=> true ,
                    "status" 					=> 'refunded',
                    "transaction_id"			=> $transaction->gateway_transaction_id                    
                );
                
                return $result;
            }
		
		} catch (\Throwable $ex) {
			\Log::error($ex->__toString());

            return array(
                "success" 			=> false ,
                "type" 				=> 'api_refund_error' ,
                "code" 				=> 'api_refund_error',
                "message" 			=> $ex->getMessage(),
				"transaction_id" 	=> $transaction->gateway_transaction_id
			);
		}
    }

    /**
     * Refund a charge that has previously been created
     *
     * @param Transaction   $transaction
     * @param Payment       $payment 
     * 
     * @return Array ['success', 'status', 'transaction_id']
     */      
    public function refund(Transaction $transaction, Payment $payment)
    {
        
		try {
            $this->setApi();
			$response = $this->api->refund($transaction);
			
			if($response->success && $response->data->Status == self::REFUND_SUCCESS)
            {
                $result = array(
                    "success" 					=> true ,
                    "status" 					=> 'refunded',
                    "transaction_id"			=> $transaction->gateway_transaction_id                    
                );
                
                return $result;
            }
		
		} catch (\Throwable $ex) {
			\Log::error($ex->__toString());

            return array(
                "success" 			=> false ,
                "type" 				=> 'api_refund_error' ,
                "code" 				=> 'api_refund_error',
                "message" 			=> $ex->getMessage(),
				"transaction_id" 	=> $transaction->gateway_transaction_id
			);
		}
    }

    /**
     * Retrieves the details of a charge that has previously been created
     *
     * @param Transaction   $transaction
     * 
     * @return Array ['success', 'transaction_id', 'amount', 'destination', 'status', 'card_last_digits']
     */       
    public function retrieve(Transaction $transaction, Payment $payment = null)
    {
        $transactionId = $transaction->gateway_transaction_id;

        $this->setApi();
		$response = $this->api->retrieve($transaction);

		if(!$response->success)
		{
			\Log::error($response->message);

			return array(
				"success" 			=> false ,
				"type" 				=> 'api_retrieve_error' ,
				"code" 				=> 'api_retrieve_error',
				"message" 			=> $response->message
			);
		}

		return array(
			'success' 			=> true,
			'transaction_id' 	=> $response->data->Payment->PaymentId,
			'amount' 			=> $response->data->Payment->Amount,
			'destination' 		=> '',	
			'status' 			=> $response->data->Payment->Status == 2 ? 'paid' : $response->data->Payment->Status,
			'card_last_digits' 	=> $payment ? $payment->last_four : '',
		);
    }

    /**
     *  Create a new credit card
     *
     * @param Payment       $payment
     * @param User          $user               The customer that this card belongs to
     * 
     * @return Array ['success', 'token', 'card_token', 'customer_id', 'card_type', 'last_four']
     */      
    public function createCard(Payment $payment, User $user = null)
    {
        $cardNumber 			= $payment->getCardNumber();

		// $cpf = $this->cleanCpf($user->document);

		$result = array(
			'success'		=>	true,
			'customer_id'	=>	'',
			'last_four'		=>	substr($cardNumber, -4),
			'card_type'		=>	detectCardType($cardNumber),
            'card_token'	=>	'',
            'token'         => '',
            'gateway'       => 'braspag_cielo_ecommerce'
		);

		return $result;
    }

    /**
     *  Delete a existing credit card
     *
     * @param Payment       $payment
     * @param User          $user               The customer that this card belongs to
     * 
     * @return Array ['success']
     */      
    public function deleteCard(Payment $payment, User $user = null)
    {
        $result = array (
			'success'	=>	true
		);
		return $result;
    }

    //finish
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

    //finish
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

    /**
     *  Create accounts for users
     *
     * @param LedgerBankAccount       $ledgerBankAccount
     * 
     * @return Array ['success', 'recipient_id']
     */      
    public function createOrUpdateAccount(LedgerBankAccount $ledgerBankAccount)
    {
        try {
            $this->setApi();
            $response = $this->api->getBrasPagAccount($ledgerBankAccount->recipient_id);

            if ($response->success) {
                $result = array(
                    'success'       => true,
                    'recipient_id'   => $ledgerBankAccount->recipient_id
                );
                $ledgerBankAccount->recipient_id = $response->data->MerchantId;
                $ledgerBankAccount->save();
            } else {
                $newAccount = $this->api->createOrUpdateAccount($ledgerBankAccount);

                if ($newAccount->success) {
                    $ledgerBankAccount->recipient_id = $newAccount->data->MerchantId;
                    $ledgerBankAccount->save();
                    $result = array(
                        'success'       => true,
                        'recipient_id'   => $ledgerBankAccount->recipient_id
                    );
                } else {
                    $result = array(
                        'success'       => false,
                        'recipient_id'   => ""
                    );
                }
            }
    
            return $result;
           
        } catch (\Throwable $ex) {
            \Log::error($ex->__toString());

			$result = array(
				"success" 					=> false ,
				"recipient_id"				=> 'empty',
				"type" 						=> 'api_bankaccount_error' ,
				"code" 						=> $ex->getReturnCode() ,
				"message" 					=> trans("empty.".$ex->getMessage())
			);

			return $result;
        }
    }

    /**
     *  Return a gateway fee
     * 
     * @return Decimal
     */        
    public function getGatewayFee()
    {
        return 0; //if change this value, needs change on api
    }

    /**
     *  Return a gateway tax
     * 
     * @return Decimal
     */      
    public function getGatewayTax()
    {

    }

    /**
     *  Return a date for the next compensation
     * 
     * @return Carbon
     */      
    public function getNextCompensationDate(){
		$carbon = Carbon::now();
		$compDays = Settings::findByKey('compensate_provider_days');
		$addDays = ($compDays || (string)$compDays == '0') ? (int)$compDays : 31;
		$carbon->addDays($addDays);
		
		return $carbon;
	}

    /**
     *  Return a bool value that determine if auto transfer to provider is enabled
     * 
     * @return bool
     */     
    public function checkAutoTransferProvider()
    {
        try
        {
            if(Settings::findByKey(self::AUTO_TRANSFER_PROVIDER) == "1")
                return(true);
            else
                return(false);
        }
        catch(Exception$ex)
        {
            \Log::error($ex);

            return(false);
        }
    }

    public function pixCharge($amount, $holder, $provider = null, $providerAmount = null)
    {
        \Log::error('pix_not_implemented');
        return array(
            "success" 			=> false,
            "qr_code_base64"    => '',
            "copy_and_paste"    => '',
            "transaction_id" 	=> ''
        );
    }

    public function retrievePix($transaction_id, $request = null)
    {
        \Log::error('retrieve_pix_not_implemented');
        return array(
            "success" 			=> false,
			'paid'				=> false,
			"value" 			=> '',
            "qr_code_base64"    => '',
            "copy_and_paste"    => ''
        );
    }

    /**
     *  Return a date for the next compensation
     * 
     * @return Password
     */ 
    // public function createDirectPassword($encryptKey, $encryptValue);

    /**
     *  Return a date for the next compensation
     * 
     * @return Token
     */

    private static function getChargeStatus($split, $capture)
    {
        switch ($split) {
            case false:
                switch ($capture) {
                    case false:
                        return self::CHARGE_SUCCESS;
                        break;
                    
                    case true:
                        return self::CAPTURE_SUCCESS;
                        break;
                    default:
                        # code...
                        break;
                }
                break;
            case true:
                switch ($capture) {
                    case false:
                        return self::CHARGE_SUCCESS;
                        break;
                    
                    case true:
                        return self::CAPTURE_SUCCESS;
                        break;
                    default:
                        # code...
                        break;
                }
                break;
            default:
                # code...
                break;
        }
    }
}