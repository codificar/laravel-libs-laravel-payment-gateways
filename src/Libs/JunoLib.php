<?php

namespace Codificar\PaymentGateways\Libs;
use Carbon\Carbon;

use Codificar\PaymentGateways\Libs\JunoApi;

use ApiErrors;

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

       
    }

    public function billetCharge($amount, $client, $postbackUrl = null, $billetExpirationDate, $billetInstructions)
    {
        
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
        \Log::error('capture_not_implemented');

        return array(
            "success" 			=> false ,
            "type" 				=> 'api_capture_error' ,
            "code" 				=> 'api_capture_error',
            "message" 			=> 'capture_not_implementd',
            "transaction_id" 	=> ''
        );
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
        
		
    }
      
    public function retrieve(Transaction $transaction, Payment $payment = null)
    {
       

		
    }
     
    //Metodo de criar cartao do juno nao e aqui, pois o fluxo de criacao e diferente. Deve ser criado em JunoController
    //Method of creating Juno Card is not here, as the creation flow is different. Must be created in JunoController
    public function createCard(Payment $payment, User $user = null)
    {
        \Log::error('create_card_error: Juno Create Card method is not here, is from JunoController');

        return array(
            "success" 			=> false ,
            "type" 				=> 'api_card_error' ,
            "code" 				=> 'api_card_error',
            "message" 			=> 'Juno Create Card method is not here, is from JunoController',
            "transaction_id" 	=> ''
        );
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