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
		//Se for para realizar a captura sem pre autorizacao, chama o metodo capture
		if($capture) {
			return $this->chargeAndCapture($amount, $payment);
		} 
		//Se nao for para realizar a captura, apenas verifica o saldo. Se o saldo for maior ou igual o preco da corrida, entao "simulamos" uma pre autorizacao.
		else {
			\Log::debug("pre autorizacao carto");
			try {
				$response = CartoApi::checkCardBalance($payment, $amount, $user);
				if ($response->success) {
					$result = array(
						'success'			=>	true,
						'captured'			=>	'false',
						'transaction_id'	=>	$response->transaction_id,
						'paid'				=>	'authorized',
						'status'			=>	$response->status
					);
					return $result;
				} else {
					throw new Exception("Error Processing Request", 1);
				}
			} catch (\Throwable $ex) {
				\Log::error($ex);
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

	public function chargeAndCapture($amount, Payment $payment) {
		try {
			$response = CartoApi::capture($amount, $payment);

			if ($response->success) {
				\Log::debug("caiu no sucesso carto! " . $response->transaction_id);
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
		  
	public function capture(Transaction $transaction, $amount, Payment $payment = null)
	{
		return $this->chargeAndCapture($amount, $payment);
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
	public function billetVerify ($request, $transaction_id = null)
	{}

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