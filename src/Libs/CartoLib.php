<?php
namespace Codificar\PaymentGateways\Libs;
use Carbon\Carbon;

use Codificar\PaymentGateways\Libs\CartoApi;

use Payment;
use Provider;
use Transaction;
use User;
use LedgerBankAccount;
use Settings;
use ApiErrors;

class CartoLib implements IPayment
{
	const AUTO_TRANSFER_PROVIDER = 'auto_transfer_provider_payment';
	  
	public function chargeWithSplit(Payment $payment, Provider $provider, $totalAmount, $providerAmount, $description, $capture = true, User $user = null)
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
    
    public function charge(Payment $payment, $amount, $description, $capture = false, User $user = null)
	{
		try {
			$response = CartoApi::capture($amount, $payment);

			if ($response->success) {
				\Log::debug("caiu no sucesso! " . $response->transaction_id);
				$result = array (
					'success' 		 => true,
					'captured' 		 => true,
					'paid' 			 => true,
					'status' 		 => 'paid',
					'transaction_id' => $response->transaction_id
				);
				return $result;
			}
		} catch (\Throwable $th) {
			
			return array(
				"success" 	=> false ,
				'data' 		=> null,
				'transaction_id' => '',
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
			$response = CartoApi::capture($amount, $payment, $capture = true);

			if ($response->success) {
				$result = array (
					'success' 		 => true,
					'captured' 		 => $capture,
					'paid' 			 => $capture,
					'status' 		 => $capture ? 'paid' : 'authorized',
					'transaction_id' => $response->transaction_id
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
            "type" 				=> 'api_capture_error' ,
            "code" 				=> 'api_capture_error',
            "message" 			=> 'split_not_implementd',
			"transaction_id" 	=> $transaction->gateway_transaction_id
		);
	}
          
    public function refund(Transaction $transaction, Payment $payment)
	{
		try {
			$response = CartoApi::refund($transaction, $payment);
			if ($response->success) {
				$result = array(
					'success'	=>	$response->success,
					"status" 			=> 'refunded' ,
                	"transaction_id" 	=> $response->transaction_id ,
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
		 
	public function retrieve(Transaction $transaction, Payment $payment = null)
	{

	}
		  
	public function createCard(Payment $payment, User $user = null)
	{
		$cardNumber 			= $payment->getCardNumber();
		
		$result = array(
			'success'		=>	true,
			'customer_id'	=>	'',
			'last_four'		=>	substr($cardNumber, -4),
			'card_type'		=>	'terracard',
			'card_token'	=>	'',
			'token'			=>	'',
			'gateway'		=>	'carto'
		);

		return $result;
	}
		  
	public function deleteCard(Payment $payment, User $user = null)
	{
		$result = array (
			'success'	=>	true
		);
		return $result;
	}

    public function createOrUpdateAccount(LedgerBankAccount $ledgerBankAccount)
	{

	}
           
    public function getGatewayFee()
	{

	}
          
    public function getGatewayTax()
	{

	}
          
    public function getNextCompensationDate()
	{
		$providerTransferDays = Settings::getProviderTransferDay();
		$now = Carbon::now();

		if ($providerTransferDays) {
			$days = $now->addDays($providerTransferDays);
		} else {
			$days = $now->addDays(31);
		}

		return $days;
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
     * Charge with billet
     *
     * @param Decimal       $amount
     * @param Object        $client                 User / Provider instance
     * @param String        $postbackUrl            Url to receive gateway webhook notifications
     * @param String        $billetExpirationDate   Billet expiration date
     * @param String        $billetInstructions     Instructions to print in billet file
     * 
     * @return Array ['success', 'status', 'captured', 'paid', 'transaction_id', 'billet_url', 'billet_expiration_date']
     */      
	public function billetCharge($amount, $client, $postbackUrl, $billetExpirationDate, $billetInstructions)
	{}

    /**
     * Check the notification from gateway webhook
     *
     * @param Object $request Body params received from gateway notification
     * 
     * @return Array ['success', 'status', 'transaction_id']
     */      
	public function billetVerify ($request)
	{}
}