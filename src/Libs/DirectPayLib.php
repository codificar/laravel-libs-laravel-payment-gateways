<?php

namespace Codificar\PaymentGateways\Libs;
use Carbon\Carbon;

use Codificar\PaymentGateways\Libs\DirectPayApi;

//models do sistema
use Payment;
use Provider;
use Transaction;
use User;
use LedgerBankAccount;
use Settings;

class DirectPayLib  implements IPayment
{
	const AUTO_TRANSFER_PROVIDER = 'auto_transfer_provider_payment';


	public function chargeWithSplit(Payment $payment, Provider $provider, $totalAmount, $providerAmount, $description, $capture = true, User $user = null){
		$response = $this->charge($payment, $totalAmount, $description, $capture, $user);
        
        return $response;
	}

	public function charge(Payment $payment, $amount, $description, $capture = true, User $user = null)
	{
		try {
			$response = DirectPayApi::chargeOrCapture($amount, $capture, $payment);

			if ($response->data->responseMessage = 'AUTHORIZED' && $response->success) {
				$result = array (
					'success' 		 => true,
					'captured' 		 => $capture,
					'paid' 			 => $capture,
					'status' 		 => $capture ? 'paid' : 'authorized',
					'transaction_id' => $response->data->paymentToken
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

	public function capture(Transaction $transaction, $amount, Payment $payment = null)
	{
		try {
			$response = DirectPayApi::capture($amount, $transaction);

			if ($response->data->responseMessage = 'PROCESSED' && $response->success) {
				$result = array (
					'success' 		 => true,
					'captured' 		 => true,
					'paid' 			 => true,
					'status' 		 => 'paid',
					'transaction_id' => $response->data->paymentToken
				);
				return $result;
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

	public function refundWithSplit(Transaction $transaction, Payment $payment)
	{
		\Log::error('split_not_implemented');
        
        return array(
            "success" 			=> false ,
            "type" 				=> 'api_refund_error' ,
            "code" 				=> 'api_refund_error',
            "message" 			=> 'split_not_implementd',
            "transaction_id" 	=> $transaction->gateway_transaction_id
        );
    }

	public function refund(Transaction $transaction, Payment $payment)
	{
		$transactionId = $transaction->gateway_transaction_id;

		try {
			$response = DirectPayApi::cancelPayment($transactionId);
			
			if(!$response->success)
            {
                \Log::error($response->message);

                return array(
                    "success" 					=> false ,
                    "type" 						=> 'api_refund_error',
                    "code" 						=> 'api_refund_error',
                    "message" 					=> $response->message
                );                
            }            

            return array(
                "success" 			=> true ,
                "status" 			=> 'refunded' ,
                "transaction_id" 	=> $transaction->gateway_transaction_id ,
            );		

		
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

	public function retrieve(Transaction $transaction, Payment $payment = null)
	{
		$transactionId = $transaction->gateway_transaction_id;

		
		$response = DirectPayApi::getPaymentInfo($transactionId);

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
			'transaction_id' 	=> $response->data->paymentToken,
			'amount' 			=> $transaction->gross_value,
			'destination' 		=> '',	
			'status' 			=> $response->data->responseMessage,
			'card_last_digits' 	=> $payment->last_four,
		);
		
	}

	public function createCard(Payment $payment, User $user = null)
	{
		$cardNumber 			= $payment->getCardNumber();
		$cardExpirationMonth 	= $payment->getCardExpirationMonth();
		$cardExpirationYear 	= $payment->getCardExpirationYear();
		$cardCvc 				= $payment->getCardCvc();
		$cardHolder 			= $payment->getCardHolder();
		$userName				= $user->first_name." ".$user->last_name;
		$userDocument				= str_replace(".", "", $user->document);

		$cpf = $this->cleanCpf($user->document);

		$response = DirectPayApi::createCard($payment, $user);

		$result = array(
			'success'		=>	true,
			'customer_id'	=>	$response->data->cardHolderUUID,
			'last_four'		=>	substr($cardNumber, -4),
			'card_type'		=>	detectCardType($cardNumber),
			'card_token'	=>	$response->data->cardUUID,
			'gateway'		=>	'directpay'
		);

		return $result;

		
	}
	public function deleteCard(Payment $payment, User $user = null)
	{
		// $result = array (
		// 	'success'	=>	true
		// );
		// return $result;

		// $payment = Payment::getFirstOrDefaultPayment($user->id);
		try {
			$response = DirectPayApi::deleteCard($payment->card_token);

			if ($response->success == true) {
				$result = array (
					'success'	=>	true
				);
				return $result;
			} else {
				throw new Exception('createCard: '. $response['message']);
			}
		} catch (\Throwable $th) {
			
			return array(
				"success" 		=> false ,
				'data' 			=> null,
				'error' 		=> array(
					"code" 		=> ApiErrors::CARD_ERROR,
					"messages" 	=> array(trans('creditCard.customerCreationFail'))
				)
			);
		}
		
	}

	public function createOrUpdateAccount(LedgerBankAccount $ledgerBankAccount)
	{
		$result = array(
			"success" 					=> true,
			"recipient_id"				=> 'empty'
		);

		return $result;
	}

	public function getGatewayFee()
	{
		return 0.5 ;
	}

	public function getGatewayTax()
	{
		return 0.0492 ;
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
	
	private function cleanCpf($cpf)
	{
		$cpf = trim($cpf);
		$cpf = str_replace(".", "", $cpf);
		$cpf = str_replace(",", "", $cpf);
		$cpf = str_replace("-", "", $cpf);
		$cpf = str_replace("/", "", $cpf);
		return $cpf;
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
		\Log::error('billet_charge_not_implemented_in_stripe_gateway');

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

	public function billetVerify($request, $transaction_id = null)
	{
		\Log::error('billet_charge_not_implemented_in_stripe_gateway');

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

}