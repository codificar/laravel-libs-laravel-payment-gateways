<?php

namespace Codificar\PaymentGateways\Libs;

use Codificar\PaymentGateways\Libs\BancoInterApi;

use Payment;
use Provider;
use Transaction;
use User;
use LedgerBankAccount;

class BancoInterLib implements IPayment
{
    /**
     * Payment status code
     */
    const CODE_NOTFINISHED  =   0;
    const CODE_AUTHORIZED   =   1;
    const CODE_CONFIRMED    =   2;
    const CODE_DENIED       =   3;
    const CODE_VOIDED       =   10;
    const CODE_REFUNDED     =   11;
    const CODE_PENDING      =   12;
    const CODE_ABORTED      =   13;
    const CODE_SCHEDULED    =   20;

    /**
     * Payment status string
     */
    const PAYMENT_NOTFINISHED  =   'not_finished';
    const PAYMENT_AUTHORIZED   =   'authorized';
    const PAYMENT_CONFIRMED    =   'paid';
    const PAYMENT_DENIED       =   'denied';
    const PAYMENT_VOIDED       =   'voided';
    const PAYMENT_REFUNDED     =   'refunded';
    const PAYMENT_PENDING      =   'pending';
    const PAYMENT_ABORTED      =   'aborted';
    const PAYMENT_SCHEDULED    =   'scheduled';

    const CHARGE_SUCCESS = 1;
    const CAPTURE_SUCCESS = 2;
    const REFUND_SUCCESS = 10;
    const WAITING_PAYMENT = 'waiting_payment';

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
    public function chargeWithSplit(Payment $payment, Provider $provider, $totalAmount, $providerAmount, $description, $capture = true, User $user = null){

        \Log::error('chage_split_not_implemented');

        return array(
            "success" 			=> false ,
            "type" 				=> 'api_capture_error',
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
    public function charge(Payment $payment, $amount, $description, $capture = true, User $user = null, Provider $provider = null){
        
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
    public function billetCharge($amount, $client, $postbackUrl, $billetExpirationDate, $billetInstructions){
        
        try {
            $response = BancoInterApi::billetCharge($amount, $client, $postbackUrl, $billetExpirationDate, $billetInstructions);

            if ($response) {
                return array (
                    'success' => true,
                    'captured' => true,
                    'paid' => false,
                    'status' => self::WAITING_PAYMENT,
                    'transaction_id' => $response->nossoNumero,
                    'billet_url' => $response->url,
                    'digitable_line' => $response->linhaDigitavel,
                    'billet_expiration_date' => $response->expirationDate
                );
            } else {
                return array(
                    "success" 				=> false ,
                    "type" 					=> 'api_charge_error',
                    "code" 					=> '',
                    "message" 				=> '',
                    "transaction_id"		=> ''
                );
            }
        } catch (\Throwable $th) {
            \Log::error($th->getMessage());

			return array(
				"success" 				=> false ,
				"type" 					=> 'api_charge_error',
				"code" 					=> '',
				"message" 				=> $th->getMessage(),
				"transaction_id"		=> ''
			);
        }
    }

    /**
	 * Método para realizar cobrança via boleto
	 * @param $payment Payment - instância do pagamento com dados do cartão
	 * @param $items - Itens do boleto (formato dos itens ['name' => , 'amount' => 1, 'value' => (inteiro representando centavos)])
	 * @param $user User - usuário da transação
	 * @param $expire - data de vencimento da fatura
	 * @param $message - mensagem opcional a ser apresentada no boleto
	 * @return array
	 */
	public function invoiceBilletCharge(Payment $payment = null, $items, User $user = null, $expire, $message = '', $invoice_id)
	{
        try {
            $response = BancoInterApi::billetCharge($items['amount'], $user, null, $expire, $message);

            if ($response) {
                return array (
                    'success'           => true,
                    'data'              => array(
                        'pdf'           => array(
                            'charge'    => $response->url // Projetos que usam essa função buscam o link para o boleto dessa forma ['data']['pdf']['charge']
                        )
                    )
                );
            } else {
                return array(
                    "success" 				=> false ,
                    "type" 					=> 'api_charge_error',
                    "code" 					=> '',
                    "message" 				=> '',
                    "transaction_id"		=> ''
                );
            }
        } catch (\Throwable $th) {
            \Log::error($th->getMessage());

			return array(
				"success" 				=> false ,
				"type" 					=> 'api_charge_error',
				"code" 					=> '',
				"message" 				=> $th->getMessage(),
				"transaction_id"		=> ''
			);
        }
	}

    /**
     * Check the notification from gateway webhook
     *
     * @param Object $request Body params received from gateway notification
     * 
     * @return Array ['success', 'status', 'transaction_id']
     */      
    public function billetVerify ($request, $transaction_id = null){
        
        if($transaction_id) {
			$transaction = Transaction::find($transaction_id);
			$retrieve = $this->retrieve($transaction);
			return [
				'success' => true,
				'status' => $retrieve->situacao == "PAGO" ? 'paid' : $retrieve->situacao,
				'transaction_id' => $retrieve->transaction_id
			];
		} else {
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
                'status' => $retrieve->situacao,
                'transaction_id' => $retrieve->transaction_id
            ];
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
    public function captureWithSplit(Transaction $transaction, Provider $provider, $totalAmount, $providerAmount, Payment $payment = null){
        
        \Log::error('capture_split_not_implemented');

        return array(
            "success" 			=> false ,
            "type" 				=> 'api_capture_error',
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
    public function capture(Transaction $transaction, $amount, Payment $payment = null){
       
        \Log::error('capture_not_implemented');

        return array(
            "success" => false,
            "type" => 'api_charge_error',
            "code" => 'api_charge_error',
            "message" => trans("paymentError.noAutoTransferProviderPayment"),
            "transaction_id" => ''
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
    public function refundWithSplit(Transaction $transaction, Payment $payment){
        
        \Log::error('refund_split_not_implemented');

        return array(
            "success" 			=> false ,
            "type" 				=> 'api_capture_error',
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
    public function refund(Transaction $transaction, Payment $payment){

        \Log::error('refund_not_implemented');

        return array(
            "success" 			=> false,
            "type" 				=> 'api_capture_error',
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
    public function retrieve(Transaction $transaction, Payment $payment = null){
        $transactionId = $transaction->gateway_transaction_id;

		
        $response = BancoInterApi::retrieve($transaction);
		if(!$response->success)
		{
			\Log::error($response->message);

			return array(
				"success" 			=> false,
				"type" 				=> 'api_retrieve_error',
				"code" 				=> 'api_retrieve_error',
				"message" 			=> $response['message']
			);            
		}

		return array(
			'success' 			=> true,
			'transaction_id' 	=> $response->data->Payment->PaymentId,
			'amount' 			=> $response->data->Payment->Amount,
			'destination' 		=> '',	
			'status' 			=> $response->data->Payment->Status == 2 ? 'paid' : strval($response->data->Payment->Status),
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
    public function createCard(Payment $payment, User $user = null){

        \Log::error('create_card_not_implemented');

        return array(
            "success" 			=> false,
            "type" 				=> 'api_capture_error',
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
    public function deleteCard(Payment $payment, User $user = null){
        
        \Log::error('delete_card_not_implemented');

        return array(
            "success" 			=> false,
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
    public function createOrUpdateAccount(LedgerBankAccount $ledgerBankAccount){
        \Log::error('create_account_not_implemented');

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
    public function getGatewayFee(){
        
    }

    /**
     *  Return a gateway tax
     * 
     * @return Decimal
     */
    public function getGatewayTax(){
        
    }

    /**
     *  Return a date for the next compensation
     * 
     * @return Carbon
     */
    public function getNextCompensationDate(){
        
    }


    /**
     *  Return a bool value that determine if auto transfer to provider is enabled
     * 
     * @return bool
     */
    public function checkAutoTransferProvider(){
        
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
    // public function createDirectToken();

    /**
     * Paid the debit transaction
     *
     * @param Object        $payment        Object that represents requester card.
     * @param Decimal       $amount         Decimal that represents amount paied on debit card.
     * @param String        $description    String that represents details of transaction.
     *
     * @return Array       [
     *                      'success',
	 *                      'captured',
	 *                      'paid',
	 *                      'status',
	 *                      'transaction_id'
     *                     ]
     */
    public function debit(Payment $payment, $amount, $description){
        
        \Log::error('debit_not_implemented');

        return array(
            "success" 			=> false,
            "type" 				=> 'api_debit_error',
            "code" 				=> 'api_debit_error',
            "message" 			=> 'debit_not_implemented',
            "transaction_id" 	=> ''
        );
    }

    /**
     * Paid the debit transaction with split rules
     *
     * @param Object        $payment        Object that represents requester card.
     * @param Object        $provider       Object that represents provider of the service.
     * @param Decimal       $totalAmount    A positive decimal representing how much to debit.
     * @param Decimal       $providerAmount A positive decimal representing how much provider recives into the transaction.
     * @param String        $description    String that represents details of transaction.
     *
     * @return Array       [
     *                      'success',
	 *                      'captured',
	 *                      'paid',
	 *                      'status',
	 *                      'transaction_id'
     *                     ]
     */
    public function debitWithSplit(Payment $payment, Provider $provider, $totalAmount, $providerAmount, $description){
        
        \Log::error('debit_split_not_implemented');

        return array(
            "success" 			=> false,
            "type" 				=> 'api_debit_error',
            "code" 				=> 'api_debit_error',
            "message" 			=> 'split_not_implementd',
            "transaction_id" 	=> ''
        );
    }
    
    private function formatBillet($user, $payment, $expire, $message)
	{
		//Recupera user
		$user = ($user) ? $user : $payment->User;
		return [
			'banking_billet' => [
				'expire_at' => $expire, // data de vencimento do titulo (aaaa-mm-dd)
				'message' => $message, // mensagem a ser exibida no boleto
				'customer' => $this->formatCustomer($user, false),
				// 'discount' =>$discount,
				// 'conditional_discount' => $conditional_discount
			]
		];
	}

    public function formatItemsFromFinance($finances)
	{
		$items = [];
        $amount = 0;
		foreach ($finances as $finance) {
            $amount += $finance->value * -1;
		}
        $items['amount'] = $amount;
		return $items;
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
