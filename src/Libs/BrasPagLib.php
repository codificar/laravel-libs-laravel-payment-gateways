<?php

namespace Codificar\PaymentGateways\Libs;
use Carbon\Carbon;

use Codificar\PaymentGateways\Libs\BrasPagApi;

use Ramsey\Uuid\Uuid;
use ApiErrors;
//models do sistema
use Payment;
use Provider;
use Transaction;
use User;
use LedgerBankAccount;
use Settings;

class BraspagLib implements IPayment
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

    /**
     * Braspag object
     */
    private $api;

    /**
     * Defined environment
     */
    public function __construct()
    {
        $this->api = new BraspagApi();
    }

    /**
     * Returns tokenized card on Gateway Braspag Pagador
     *
     * @param Object        $payment        Object that represents requester card.
     * @param Object        $user           Object that represents user on system.
     *
     * @return Array       [
     *                      'success',
     *                      'token',
     *                      'customer_id',
     *                      'last_four',
     *                      'card_type',
     *                      'card_token',
     *                      'gateway'
     *                     ]
     */
    public function createCard(Payment $payment, User $user = null)
    {
        try {

            $cardNumber 			= $payment->getCardNumber();

            return array(
                'success'		=>	true,
                'token'         =>	'',
                'customer_id'	=>	'',
                'last_four'		=>	substr($cardNumber, -4),
                'card_type'		=>	detectCardType($cardNumber),
                'card_token'	=>	'',
                "gateway"       =>  "braspag"
            );

        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            return $this->responseApiError('gateways.new_card_fail');
        }
    }

    //finish
    public function deleteCard(Payment $payment, User $user = null)
    {
        return array(
			'success' => true
		);
    }

    /**
     * Returns authorized transaction information on Geteway Braspag Pagador
     *
     * @param Object        $payment        Object that represents requester card.
     * @param Integer       $amount         Integer that represents amount authorized on credit card.
     * @param String        $description    String that represents details of transaction.
     * @param Boolean       $capture        Boolean that represents config to capture on charge.
     * @param Object        $user           Object that represents user on system.
     *
     * @return Array       [
     *                      'success',
	 *			            'captured',
	 *			            'paid',
	 *			            'status',
	 *			            'transaction_id'
     *                     ]
     */
    public function charge(Payment $payment, $amount, $description, $capture = true, User $user = null)
    {
        $amount = floor($amount * 100);

        if($amount <= 0)
            return $this->responseApiError('gateways.amount_negative');

        try {
            // Paliativo pois precisa alterar todas as charges do sistema para ledger
            $client = $payment->user_id ? \User::find($payment->user_id) : \Provider::find($payment->provide_id);

            if($user)
                $client = $user;

            $charge = $this->api->charge($payment, $client, $amount, $capture, BraspagApi::CREDIT_CARD, $description);

            $awaitedStatus = $capture ? self::CODE_CONFIRMED : self::CODE_AUTHORIZED;

            $chargeStatus = $charge->data->Payment->Status;
            $paymentId = $charge->data->Payment->PaymentId;

            if($awaitedStatus != $chargeStatus)
			{
                return $this->responseApiError('paymentError.refused');
			}

			return array (
				'success'        => true,
				'captured'       => $capture,
				'paid'           => $capture,
				'status'         => $capture ? self::PAYMENT_CONFIRMED : self::PAYMENT_AUTHORIZED,
				'transaction_id' => $paymentId
            );

        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            return $this->responseApiError('gateways.charge_fail');
        }
    }

    /**
     * Returns confirmed transaction information on Geteway Braspag Pagador
     *
     * @param Object        $transaction    Object that represents system transaction.
     * @param Integer       $amount         Integer that represents amount to caṕture on credit card.
     * @param Object        $payment        Object that represents requester card.
     *
     * @return Array       [
     *                      'success',
	 *			            'status',
	 *			            'captured',
	 *			            'paid',
	 *			            'transaction_id'
     *                     ]
     */
    public function capture(Transaction $transaction, $amount, Payment $payment = null)
	{
        $amount = floor($amount * 100);

        if($amount <= 0)
            return $this->responseApiError('gateways.amount_negative');

        try
        {
            $capture = $this->api->capture($transaction);
            $captureStatus = $capture->data->Status;

            if($captureStatus != self::CODE_CONFIRMED)
			{
                return $this->responseApiError('paymentError.refused');
            }

            return array(
                'success'        => true,
				'status'         => self::PAYMENT_CONFIRMED,
				'captured'       => true,
				'paid'           => true,
				'transaction_id' => $transaction->gateway_transaction_id
            );
        }
        catch (\Exception $e)
        {
            \Log::error($e->getMessage());
            return $this->responseApiError('gateways.capture_fail');
        }
    }

    /**
     * Returns transaction information on Geteway Braspag Pagador
     *
     * @param Object        $transaction    Object that represents system transaction.
     * @param Object        $payment        Object that represents requester card.
     *
     * @return Array       [
     *                      'success',
     *                      'transaction_id',
     *                      'amount',
     *                      'destination',
     *                      'status',
     *                      'card_last_digits'
     *                     ]
     */
    public function retrieve(Transaction $transaction, Payment $payment = null)
    {
        try{
            $retrieve = $this->api->retrieve($transaction);

            if(!isset($retrieve->success) || (isset($retrieve->success) && !$retrieve->success))
            {
                return $this->responseApiError('gateways.retrieve_fail');
            }

            return array(
                'success' 			=> true,
                'transaction_id' 	=> $retrieve->data->Payment->PaymentId,
                'amount' 			=> $retrieve->data->Payment->Amount,
                'destination' 		=> '',	
                'status' 			=> $this->getStatusString($retrieve->data->Payment->Status),
                'card_last_digits' 	=> $payment->last_four,
            );
        }
        catch (\Exception $e)
        {
            \Log::error($e->getMessage());
            return $this->responseApiError('gateways.retrieve_fail');
        }
    }

    /**
     * Cancel and returns transaction information on Geteway Braspag Pagador
     *
     * @param Object        $transaction    Object that represents system transaction.
     * @param Object        $payment        Object that represents requester card.
     *
     * @return Array       [
     *                      'success',
     *                      'status',
     *                      'transaction_id'
     *                     ]
     */
    public function refund(Transaction $transaction, Payment $payment)
    {
        try {
            $retrieve = $this->api->retrieve($transaction);

            $refundStatus = $retrieve->data->Payment->Status; ;

            if(
                ($refundStatus != self::CODE_NOTFINISHED && 
                $refundStatus != self::CODE_AUTHORIZED && 
                $refundStatus != self::CODE_CONFIRMED && 
                $refundStatus != self::CODE_PENDING &&
                $refundStatus != self::CODE_SCHEDULED) || 
                !$retrieve->success
            )
                return $this->responseApiError('gateways.refund_retrieve_fail');

            $refund = $this->api->refund($transaction);

            if(!isset($refund->success) || !$refund->success)
                return $this->responseApiError('gateways.refund_fail');

            return array(
                "success" 					=> true ,
                "status" 					=> self::PAYMENT_REFUNDED,
                "transaction_id"			=> $transaction->gateway_transaction_id                    
            );
        }
        catch (\Exception $e)
        {
            \Log::error($e->getMessage());
            return $this->responseApiError('gateways.refund_fail');
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
    public function billetCharge($amount, $client, $postbackUrl = null, $billetExpirationDate, $billetInstructions)
    {
        try {
            $response = $this->api->billetCharge($amount, $client, $postbackUrl, $billetExpirationDate, $billetInstructions);


            return array(
                "success" 				=> false ,
                "type" 					=> 'api_billet_error' ,
                "error" 				=> $response['message'] ,
                "message" 				=> $response['message'] ,
                "code" 					=> '400',
                "message" 				=> '',
                "transaction_id"		=> ''
            );
        } catch (\Throwable $th) {
            \Log::error($th->getMessage());

			return array(
				"success" 				=> false ,
				"type" 					=> 'api_billet_error' ,
				"code" 					=> '',
				"message" 				=> $th->getMessage(),
				"transaction_id"		=> ''
			);
        }
    }

    /**
     * Verifies billet status returned from postback by Gateway Braspag Pagador
     *
     * @param Object        $request                 Object that represents postback returned by gateway.
     *
     * @return Array       [
     *                      'success',
     *                      'status',
     *                      'transaction_id',
     *                     ]
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

    //finish
    public function chargeWithSplit(Payment $payment, Provider $provider, $totalAmount, $providerAmount, $description, $capture = true, User $user = null)
    {
        \Log::error('chage_split_not_implemented');

        return array(
            "success" 			=> false ,
            "type" 				=> 'api_chargesplit_error' ,
            "code" 				=> 'api_chargesplit_error',
            "message" 			=> 'split_not_implementd',
            "transaction_id" 	=> ''
        );
    }

    //finish
    public function refundWithSplit(Transaction $transaction, Payment $payment)
    {
        \Log::error('refund_split_not_implemented');

        return array(
            "success" 			=> false ,
            "type" 				=> 'api_refundsplit_error' ,
            "code" 				=> 'api_refundsplit_error',
            "message" 			=> 'split_not_implementd',
            "transaction_id" 	=> $transaction->gateway_transaction_id
        );
    }

    //finish
    public function captureWithSplit(Transaction $transaction, Provider $provider, $totalAmount, $providerAmount, Payment $payment = null)
    {
        \Log::error('capture_split_not_implemented');

        return array(
            "success" 			=> false ,
            "type" 				=> 'api_capturesplit_error' ,
            "code" 				=> 'api_capturesplit_error',
            "message" 			=> 'split_not_implementd',
            "transaction_id" 	=> $transaction->gateway_transaction_id
        );
    }

    //finish
    public function createOrUpdateAccount(LedgerBankAccount $ledgerBankAccount)
    {
        return array(
			'success' => true,
			'recipient_id' => '',
		);
    }

    /**
     * Tax used on system transaction
     */
    public function getGatewayTax()
	{
		return 0.0399;
	}

    /**
     * Fee used on system transaction
     */
	public function getGatewayFee()
	{
		return 0.5 ;
    }
    
    /**
     * Boolean used on split config
     */
    public function checkAutoTransferProvider()
    {
        return false;
    }

    /**
     * Returns next compensation date
     */
    public function getNextCompensationDate()
    {
		$carbon = Carbon::now();
		$carbon->addDays(31);
		return $carbon ;
    }
    
    /**
     * Returns translated fails response on lib
     *
     * @param String        $message    String to translate.
     *
     * @return Array       [
     *                      'success',
     *                      'message'
     *                     ]
     */
    private function responseApiError($message)
    {
        \Log::error($message);

        return array(
            "success" 			=> false,
            "message" 			=> trans($message),
            "transaction_id"    => ''
        );
    }

    /**
     * Returns status string based on status code
     *
     * @param Integer        $statusCode    Payment status code captured on gateway.
     *
     * @return String                       String related to the payment status code.
     *
     */
    private function getStatusString($statusCode)
    {
        switch ($statusCode) {
            case self::CODE_NOTFINISHED:
                return self::PAYMENT_NOTFINISHED;
            case self::CODE_AUTHORIZED:
                return self::PAYMENT_AUTHORIZED;
            case self::CODE_CONFIRMED:
                return self::PAYMENT_CONFIRMED;
            case self::CODE_DENIED:
                return self::PAYMENT_DENIED;
            case self::CODE_VOIDED:
                return self::PAYMENT_VOIDED;
            case self::CODE_REFUNDED:
                return self::PAYMENT_REFUNDED;
            case self::CODE_PENDING:
                return self::PAYMENT_PENDING;
            case self::CODE_ABORTED:
                return self::PAYMENT_ABORTED;
            case self::CODE_SCHEDULED:
                return self::PAYMENT_SCHEDULED;
            default:
                return 'not_geted';
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
