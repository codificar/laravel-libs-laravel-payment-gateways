<?php

namespace Codificar\PaymentGateways\Libs;
use Carbon\Carbon;

use Codificar\PaymentGateways\Libs\AdiqApi;

use ApiErrors;
use Exception;
//models do sistema
use Payment;
use Provider;
use Transaction;
use User;
use LedgerBankAccount;
use Settings;

Class AdiqLib implements IPayment
{
    const AUTO_TRANSFER_PROVIDER = 'auto_transfer_provider_payment';

    //finish
    public function chargeWithSplit(Payment $payment, Provider $provider, $totalAmount, $providerAmount, $description, $capture = true, User $user = null)
    {
        \Log::error('chage_split_not_implemented_in_adiq_gateway');

        return array(
            "success" 			=> false ,
            "type" 				=> 'api_capture_error' ,
            "code" 				=> 'api_capture_error',
            "message" 			=> 'split_not_implementd',
            "transaction_id" 	=> ''
        );
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
        $paymentId = null ;

        try {
            $response = AdiqApi::chargeWithOrNotSplit($payment, null, $amount, null, $description, $capture, false);

            if($response && isset($response->data) && $response->data && isset($response->data->paymentAuthorization)) {
                $paymentId = $response->data->paymentAuthorization->paymentId;
            } else {
                $paymentId = -1;
            }

			if (
                isset($response->success) && 
                $response->success && 
                isset($response->data) && 
                $response->data->paymentAuthorization->description == 'Sucesso'
            )
            {
				$result = array (
                    'success' => true,
                    'captured' => $capture,
                    'paid' => $capture,
                    'status' => $capture ? 'paid' : 'authorized',
                    'transaction_id' => $paymentId
                );
				return $result;
			} else {
                return array(
                    "success" 	=> false ,
                    'data' 		=> null,
                    'error' 	=> isset($response->message) && isset($response->message) 
                        ? $response->message
                        : trans('creditCard.chargeFail'),
                    'message' 	=> isset($response->message) && isset($response->message) 
                        ? $response->message
                        : trans('creditCard.chargeFail'),
                    'transaction_id'    => $paymentId,
                    'error' 	=> array(
                        "code" 		=> ApiErrors::CARD_ERROR,
                        "messages" 	=> array(trans('creditCard.customerCreationFail'))
                    )
                );
            }
		} catch (Exception $th) {
			\Log::error($th->getMessage() . $th->getTraceAsString());
			return array(
				"success" 	=> false ,
				'data' 		=> null,
				'transaction_id'	=> $paymentId,
                'error' 	=> $th->getMessage(),
                'message' 	=> $th->getMessage(),
				'error' 	=> array(
					"code" 		=> ApiErrors::CARD_ERROR,
					"messages" 	=> array(trans('creditCard.customerCreationFail'))
				)
            );
            
		}
    }

    //finish
    public function billetCharge($amount, $client, $postbackUrl = null, $billetExpirationDate, $billetInstructions)
    {
        \Log::error('billet_charge_not_implemented_in_adiq_gateway');

        return array(
            "success" 				=> false,
            "type" 					=> 'api_charge_error',
            "code" 					=> '',
            "message" 				=> 'api_charge_error',
            "transaction_id"		=> ''
        );
    }

    //finish
	public function billetVerify ($request, $transaction_id = null)
	{
        \Log::error('billet_verify_not_implemented_in_adiq_gateway');

		return array (
			'success' => false,
			'captured' => false,
			'paid' => false,
			'status' => false,
			'transaction_id' => null,
			'billet_url' => '',
			'billet_expiration_date' => ''
		);
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
        \Log::error('capture_split_not_implemented_in_adiq_gateway');

        return array(
            "success" 			=> false ,
            "type" 				=> 'api_capture_error' ,
            "code" 				=> 'api_capture_error',
            "message" 			=> 'split_not_implementd',
            "transaction_id" 	=> $transaction->gateway_transaction_id
        );
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
        try {
			$response = AdiqApi::capture($transaction, $amount);

			if (
                isset($response->success) && 
                $response->success && 
                isset($response->data) && 
                $response->data->captureAuthorization->description == 'Sucesso'
            )
            {
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
                    'error' 	=> isset($response->message) && isset($response->message) 
                        ? $response->message
                        : trans('creditCard.chargeFail'),
                    'message' 	=> isset($response->message) && isset($response->message) 
                        ? $response->message
                        : trans('creditCard.chargeFail'),
                    'transaction_id'    => '',
                    'error' 	=> array(
                        "code" 		=> ApiErrors::CARD_ERROR,
                        "messages" 	=> array(trans('creditCard.customerCreationFail'))
                    )
                );
            }
		} catch (\Throwable $th) {
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

    //finish  
    public function refundWithSplit(Transaction $transaction, Payment $payment)
    {
        \Log::error('refund_split_not_implemented_in_adiq_gateway');

        return array(
            "success" 			=> false ,
            "type" 				=> 'api_capture_error' ,
            "code" 				=> 'api_capture_error',
            "message" 			=> 'split_not_implementd',
            "transaction_id" 	=> $transaction->gateway_transaction_id
        );
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
			$response = AdiqApi::refund($transaction);
			
			if(
                isset($response->success) && 
                $response->success && 
                isset($response->data) && 
                $response->data->cancelAuthorization->description == 'Sucesso')
            {
                $result = array(
                    "success" 					=> true ,
                    "status" 					=> 'refunded',
                    "transaction_id"			=> $transaction->gateway_transaction_id                    
                );
                
                return $result;
            } else {
                return array(
                    "success" 	=> false ,
                    'data' 		=> null,
                    'error' 	=> isset($response->message) && isset($response->message) 
                        ? $response->message
                        : trans('creditCard.chargeFail'),
                    'message' 	=> isset($response->message) && isset($response->message) 
                        ? $response->message
                        : trans('creditCard.chargeFail'),
                    'transaction_id'    => '',
                    'error' 	=> array(
                        "code" 		=> ApiErrors::CARD_ERROR,
                        "messages" 	=> array(trans('creditCard.customerCreationFail'))
                    )
                );
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
        $response = AdiqApi::retrieve($transaction);
		if(!$response->success)
		{
			\Log::error($response->message);

			return array(
				"success" 			=> false ,
				"type" 				=> 'api_retrieve_error' ,
				"code" 				=> 'api_retrieve_error',
				"message" 			=> isset($response) && isset($response->message) 
                    ? $response->message
                    : 'error: ' . json_encode($response)
			);            
		}

        if(isset($response->data) && isset($response->data->paymentAuthorization))
            $status = 'authorized';
        else if(isset($response->data) && isset($response->data->captureAuthorization))
            $status = 'paid';
        else if(isset($response->data) && isset($response->data->cancelAuthorization))
            $status = 'refunded';
        else{
            \Log::debug('Adiq real retrieve data: ' . print_r($response,true));

            $status = 'refused';
        }

		return array(
			'success' 			=> true,
			'transaction_id' 	=> $transaction->gateway_transaction_id,
			'amount' 			=> $transaction->gross_value,
			'destination' 		=> '',	
			'status' 			=> $status,
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
		$cardExpirationMonth 	= $payment->getCardExpirationMonth();
		$cardExpirationYear 	= $payment->getCardExpirationYear();
		$cardCvc 				= $payment->getCardCvc();
		$cardHolder 			= $payment->getCardHolder();
		$userName				= $user->first_name." ".$user->last_name;
		$userDocument				= str_replace(".", "", $user->document);

		// $cpf = $this->cleanCpf($user->document);

		$result = array(
			'success'		=>	true,
			'customer_id'	=>	'',
			'last_four'		=>	substr($cardNumber, -4),
			'card_type'		=>	detectCardType($cardNumber),
            'card_token'	=>	'',
            'token'	        =>	'',
            'gateway'       => 'adiq'
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
    public function deleteCard(Payment $payment, User $user = null){
        $result = array (
			'success'	=>	true
		);
		return $result;
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
        \Log::error('create_account_not_implemented_in_adiq_gateway');

        return array(
			'success' => true,
			'recipient_id' => '',
		);
    }

    /**
     *  Return a gateway fee
     * 
     * @return Decimal
     */        
    public function getGatewayFee()
    {
        return AdiqApi::getAdiqFee();
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
    public function getNextCompensationDate()
    {
        $carbon = Carbon::now();
		$carbon->addDays(31);
		return $carbon ;
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
        catch(Exception $ex)
        {
            \Log::error($ex);

            return(false);
        }
    }

    public function debit(Payment $payment, $amount, $description)
    {
        $paymentId = null ;

        try
        {
            $response = AdiqApi::debit($payment, null, $amount, null, $description);

            if($response && isset($response->data) && isset($response->data->captureAuthorization)) {
                $paymentId = $response->data->captureAuthorization->paymentId;
            } else {
                $paymentId = -1;
            }

			if (
                $response->success && 
                isset($response->data) && 
                isset($response->data->captureAuthorization) && 
                $response->data->captureAuthorization->description == 'Sucesso'
            )
            {
				$result = array (
                    'success'       => true,
                    'captured'      => true,
                    'paid'          => true,
                    'status'        => 'paid',
                    'transaction_id'=> $paymentId
                );
				return $result;
			} else {
                return array(
                    "success"       => false,
                    "captured"      => false,
                    "message"       => trans('gateway_cielo.debit_fail'),
                    "transaction_id"=> $paymentId,
                    "paid"          => 'denied'
                );
            }
		} catch (Exception $th) {
			\Log::error('Debit Adiq error: '.$th->getMessage());
			return array(
				"success"       => false,
                "captured"      => false,
                "message"       => trans('gateway_cielo.debit_fail'),
                'transaction_id'=> $paymentId,
                "paid"          => 'not_finished'
            );
		}
    }

    //finish
    public function debitWithSplit(Payment $payment, Provider $provider, $totalAmount, $providerAmount, $description)
    {
        \Log::error('debit_split_not_implemented_in_adiq_gateway');

        return array(
            "success" 			=> false,
            "type" 				=> 'api_debit_error',
            "code" 				=> 'api_debit_error',
            "message" 			=> 'split_not_implementd',
            "transaction_id" 	=> ''
        );
    }

    public function pixCharge($amount, $holder)
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

}
