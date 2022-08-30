<?php

namespace Codificar\PaymentGateways\Libs;
use Carbon\Carbon;

use Codificar\PaymentGateways\Libs\BancrypApi;

use Payment;
use Provider;
use Transaction;
use User;
use LedgerBankAccount;
use Settings;
use ApiErrors;

Class BancrypLib implements IPayment
{
    const CHARGE_SUCCESS = 1;
    const CAPTURE_SUCCESS = 1;
    const REFUND_SUCCESS = 10;
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
            $response = BrasPagApi::chargeWithSplit($payment, $provider, $totalAmount, $providerAmount, $description, $capture, $user);
            
            $responseChargeStatus = self::getChargeStatus(true, $capture);

			if ($response->success && $response->data->Payment->Status == $responseChargeStatus) {
				$result = array (
					'success' 		    => true,
					'status' 		    => $capture,
					'captured' 			=> $capture,
					'paid' 		        => $capture ? 'paid' : 'authorized',
					'transaction_id'    => $response->data->Payment->PaymentId
				);
				return $result;
			}
		} catch (Exception $th) {
			
			return array(
				"success" 	=> false ,
				'data' 		=> null,
				'transaction_id'	=>	$response['transaction_id'],
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
    public function charge(Payment $payment, $amount, $description, $capture = true, User $user = null, Provider $provider = null)
    {
        // $user = User::find($payment->user_id);

        try {
            $response = BancrypApi::charge($amount);
            
            $responseChargeStatus = self::getChargeStatus(false, $capture);

			if ($response->success) {
				$result = array (
					'success' 		    => true,
					'status' 		    => $capture,
					'captured' 			=> $capture,
					'paid' 		        => $capture ? 'paid' : 'authorized',
                    'transaction_id'    => $response->data->payment_id,
                    'qrcode'            => $response->url
				);
				return $result;
			}
		} catch (Exception $th) {
			
			return array(
				"success" 	=> false ,
				'data' 		=> null,
				'transaction_id'	=>	$response['transaction_id'],
				'error' 	=> array(
					"code" 		=> ApiErrors::CARD_ERROR,
					"messages" 	=> array(trans('creditCard.customerCreationFail'))
				)
			);
		}
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
        \Log::error('split_not_implemented');

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
    public function capture(Transaction $transaction, $amount = null, Payment $payment = null)
    {
        try {
			$response = BancrypApi::capture($transaction);

			if ($response->success && $response->data->status == self::CAPTURE_SUCCESS) {
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
                        "code" 		=> ApiErrors::CRYPT_ERRORS,
                        "messages" 	=> array(trans('creditCard.crypt_coin_error'))
                    )
                );
            }
		} catch (\Throwable $th) {
			
			return array(
				"success" 	=> false ,
				'data' 		=> null,
				'error' 	=> array(
					"code" 		=> ApiErrors::CARD_ERROR,
					"messages" 	=> array(trans('creditCard.crypt_coin_error'))
				)
			);
		}
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
        \Log::error('split_not_implemented');

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
        
		\Log::error('refund_not_implemented');

        return array(
            "success" 			=> false ,
            "type" 				=> 'api_capture_error' ,
            "code" 				=> 'api_capture_error',
            "message" 			=> 'split_not_implementd',
            "transaction_id" 	=> $transaction->gateway_transaction_id
        );
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

		
		$response = BrasPagApi::retrieve($transaction);

		if(!$response->success)
		{
			\Log::error($response->message);

			return array(
				"success" 			=> false ,
				"type" 				=> 'api_retrieve_error' ,
				"code" 				=> 'api_retrieve_error',
				"message" 			=> $response['message']
			);            
		}

		return array(
			'success' 			=> true,
			'transaction_id' 	=> $response->data->Payment->PaymentId,
			'amount' 			=> $response->data->Payment->Amount,
			'destination' 		=> '',	
			'status' 			=> $response->data->Payment->Status,
			'card_last_digits' 	=> $payment->last_four,
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
        \Log::error('create_card_not_implemented');

        return array(
            "success" 			=> false ,
            "type" 				=> 'api_capture_error' ,
            "code" 				=> 'api_capture_error',
            "message" 			=> 'split_not_implementd',
            "transaction_id" 	=> ''
        );
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
        \Log::error('delete_card_not_implemented');

        return array(
            "success" 			=> false ,
            "type" 				=> 'api_capture_error' ,
            "code" 				=> 'api_capture_error',
            "message" 			=> 'split_not_implementd',
            "transaction_id" 	=> $transaction->gateway_transaction_id
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
        \Log::error('create_banck_account_not_implemented');

        return array(
            "success" 			=> false ,
            "type" 				=> 'api_capture_error' ,
            "code" 				=> 'api_capture_error',
            "message" 			=> 'split_not_implementd',
            "transaction_id" 	=> $transaction->gateway_transaction_id
        );
    }

    /**
     *  Return a gateway fee
     * 
     * @return Decimal
     */        
    public function getGatewayFee()
    {
        
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

    public function billetCharge($amount, $client, $postbackUrl, $billetExpirationDate, $billetInstructions)
	{
		
	}

	public function billetVerify($request, $transaction_id = null)
	{
		
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