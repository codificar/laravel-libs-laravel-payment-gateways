<?php

namespace Codificar\PaymentGateways\Libs;
use Carbon\Carbon;

use Codificar\PaymentGateways\Libs\IpagApi;

use ApiErrors;
use Exception;
//models do sistema
use Payment;
use Provider;
use Transaction;
use User;
use LedgerBankAccount;
use Requests;
use Settings;
// use RequestCharging;
use Log;

Class IpagLib implements IPayment
{
    /**
     * Payment status code
     */
    const CODE_CREATED          =   1;
    const CODE_WAITING_PAYMENT  =   2;
    const CODE_CANCELED         =   3;
    const CODE_IN_ANALISYS      =   4;
    const CODE_PRE_AUTHORIZED   =   5;
    const CODE_PARTIAL_CAPTURED =   6;
    const CODE_DECLINED         =   7;
    const CODE_CAPTURED         =   8;
    const CODE_CHARGEDBACK      =   9;
    const CODE_IN_DISPUTE       =   10;

    /**
     * Payment status string
     */
    const PAYMENT_NOTFINISHED  =   'not_finished';
    const PAYMENT_AUTHORIZED   =   'authorized';
    const PAYMENT_PAID         =   'paid';
    const PAYMENT_DENIED       =   'denied';
    const PAYMENT_VOIDED       =   'voided';
    const PAYMENT_REFUNDED     =   'refunded';
    const PAYMENT_PENDING      =   'pending';
    const PAYMENT_ABORTED      =   'aborted';
    const PAYMENT_SCHEDULED    =   'scheduled';

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
    public function chargeWithSplit(Payment $payment, Provider $provider, $totalAmount, $providerAmount, $description, $capture = true, User $user = null)
    {
        try
        {
            $response = IpagApi::chargeWithOrNotSplit($payment, $provider, $totalAmount, $providerAmount, $capture);
            $sysAntifraud = Settings::findByKey('ipag_antifraud');

            if (
                isset($response->success) && 
                $response->success && 
                isset($response->data) && 
                (
                    (
                        $sysAntifraud &&
                        isset($response->data->attributes->antifraud->status) &&
                        $response->data->attributes->antifraud->status == 'approved'
                    ) 
                    ||
                    (
                        !$sysAntifraud
                    )
                )
                && 
                (
                    $response->data->attributes->status->message == 'CAPTURED' ||
                    $response->data->attributes->status->message == 'PRE-AUTHORIZED'
                )
            ){
                $statusMessage = $response->data->attributes->status->message;
				$result = array (
					'success' 		    => true,
					'status' 		    => $statusMessage == 'CAPTURED' ? 'paid' : 'authorized',
					'captured' 			=> $statusMessage == 'CAPTURED' ? true : false,
					'paid' 		        => $statusMessage == 'CAPTURED' ? 'paid' : 'denied',
					'transaction_id'    => (string)$response->data->id
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
		} catch (Exception $th) {
			
			return array(
				"success"           =>  false ,
				'data'              =>  null,
				'transaction_id'    =>  '',
				'error'     => array(
					"code"          => ApiErrors::CARD_ERROR,
					"messages"      => array(trans('creditCard.customerCreationFail'))
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
        try
        {
            $response = IpagApi::chargeWithOrNotSplit($payment, null, $amount, null, $capture);
            $sysAntifraud = Settings::findByKey('ipag_antifraud');

			if(
                isset($response->success) && 
                $response->success && 
                isset($response->data) && 
                (
                    (
                        $sysAntifraud &&
                        isset($response->data->attributes->antifraud->status) &&
                        $response->data->attributes->antifraud->status == 'approved'
                    ) 
                    ||
                    (
                        !$sysAntifraud
                    )
                )
                &&
                (
                    $response->data->attributes->status->message == 'CAPTURED' ||
                    $response->data->attributes->status->message == 'PRE-AUTHORIZED'
                )
            ){
                $statusMessage = $response->data->attributes->status->message;
				$result = array (
                    'success'           =>  true,
                    'captured'          =>  $statusMessage == 'CAPTURED' ? true : false,
                    'paid'              =>  $statusMessage == 'CAPTURED' ? true : false,
                    'status'            =>  $statusMessage == 'CAPTURED' ? 'paid' : 'authorized',
                    'transaction_id'    =>  (string)$response->data->id
                );
				return $result;
			} else {
                return array(
                    "success"           => false ,
                    'data'              => null,
                    'transaction_id'    => -1,
                    'error' => array(
                        "code"      => ApiErrors::CARD_ERROR,
                        "messages"  => array(trans('creditCard.customerCreationFail'))
                    )
                );
            }
		} catch (Exception $th) {
            Log::error($th->__toString());

			return array(
				"success"           =>  false ,
				'data'              =>  null,
				'transaction_id'    =>  $response->data->Payment->PaymentId,
				'error' => array(
					"code"      =>  ApiErrors::CARD_ERROR,
					"messages"  =>  array(trans('creditCard.customerCreationFail'))
				)
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
    public function billetCharge($amount, $client, $postbackUrl, $billetExpirationDate, $billetInstructions)
    {
        try
        {
            //it's a system var, adm don't changes
            if(!Settings::findByKey('ipag_webhook_isset')) // if null/false criates a new hook
            {
                $responseHooks = IpagApi::retrieveHooks();

                if(
                    !isset($responseHooks->success) ||
                    !$responseHooks->success ||
                    !isset($responseHooks->data->data) ||
                    !count($responseHooks->data->data)
                ){
                    $responseHook = IpagApi::registerHook($postbackUrl);//criates a new hook

                    if(
                        !isset($responseHook->success) ||
                        !$responseHook->success ||
                        !isset($responseHook->data->id)
                    )
                        return array(
                            "success" 				=> false,
                            "type" 					=> 'api_charge_error',
                            "code" 					=> '',
                            "message" 				=> '',
                            "transaction_id"		=> ''
                        );
                }

                if($objectHook = Settings::findObjectByKey('ipag_webhook_isset'))// if have key save true
                {
                    $objectHook->value = 1;
                    $objectHook->save();
                }
            }

            $response = IpagApi::billetCharge($amount, $client, $billetExpirationDate, $billetInstructions);

            if (
                isset($response->success) ||
                $response->success ||
                isset($response->data->id)
            )
                return array (
                    'success'                   =>  true,
                    'captured'                  =>  true,
                    'paid'                      =>  false,
                    'status'                    =>  self::WAITING_PAYMENT,
                    'transaction_id'            =>  (string)$response->data->id,
                    'billet_url'                =>  $response->data->attributes->boleto->link,
                    'digitable_line'            =>  $response->data->attributes->boleto->digitable_line,
                    'billet_expiration_date'    =>  $response->data->attributes->boleto->due_date
                );
            else
                return array(
                    "success" 				=> false,
                    "type" 					=> 'api_charge_error',
                    "code" 					=> '',
                    "message" 				=> '',
                    "transaction_id"		=> ''
                );

        } catch (\Throwable $th) {
            Log::error($th->getMessage());

			return array(
				"success" 				=> false ,
				"type" 					=> 'api_charge_error' ,
				"code" 					=> '',
				"message" 				=> $th->getMessage(),
				"transaction_id"		=> ''
			);
        }
    }

    /**
	 * Trata o postback retornado pelo gateway
	 */
	public function billetVerify ($request, $transaction_id = null)
	{
        try
        {
            if(!isset($request->attributes->boleto))
                return [
                    'success'       =>  false,
                    'status'        =>  '',
                    'transaction_id'=>  ''
                ];
            
            Log::debug("postback ipag billet: " . print_r($request->all(), 1));
            if($transaction_id)
            {
                $transaction    =   Transaction::find($transaction_id);
                $retrieve       =   $this->retrieve($transaction);
                return [
                    'success'           =>  true,
                    'status'            =>  $retrieve['status'],
                    'transaction_id'    =>  $retrieve['transaction_id']
                ];
            }
            else
            {
                $postbackTransaction = $request->id;

                if (!$postbackTransaction)
                    return [
                        'success'       =>  false,
                        'status'        =>  '',
                        'transaction_id'=>  ''
                    ];
                
                $transaction    =   Transaction::getTransactionByGatewayId($postbackTransaction);
                $retrieve       =   $this->retrieve($transaction);
        
                return [
                    'success'           =>  true,
                    'status'            =>  $retrieve['status'],
                    'transaction_id'    =>  $retrieve['transaction_id']
                ];
            }
        } catch (Exception $ex) {
            Log::error($ex->getMessage());

            return [
                'success'       =>  false,
                'status'        =>  '',
                'transaction_id'=>  ''
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
    public function captureWithSplit(Transaction $transaction, Provider $provider, $totalAmount, $providerAmount, Payment $payment = null)
    {
        try
        {
            $retrievedCharge =  $this->retrieve($transaction);

            $this->checkException($retrievedCharge, 'api_retrievecapture_split_error');

            $newAmount       =  null;

            if($retrievedCharge['amount'] != $totalAmount)
                $newAmount   =  $totalAmount;

            if($newAmount > $retrievedCharge['amount'])
                $amountParam =  null;
            else
                $amountParam =  $newAmount;

			$response = IpagApi::captureWithSplit($transaction, $provider, $providerAmount, $amountParam);

			if(
                isset($response->success) && 
                $response->success && 
                isset($response->data) && 
                (
                    $response->data->attributes->status->message == 'CAPTURED' ||
                    $response->data->attributes->status->message == 'PRE-AUTHORIZED'
                )
            ){
                $statusMessage = $response->data->attributes->status->message;
				$result = array (
					'success' 		 => true,
					'captured' 		 => $statusMessage == 'CAPTURED' ? true : false,
					'paid' 			 => $statusMessage == 'CAPTURED' ? true : false,
					'status' 		 => $statusMessage == 'CAPTURED' ? 'paid' : 'authorized',
					'transaction_id' => (string)$response->data->id
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
            Log::error($th->__toString());
			
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
     * @param Decimal       $totalAmount        A positive decimal representing how much to capture
     * @param Payment       $payment     
     * 
     * @return Array ['success', 'status', 'captured', 'paid', 'transaction_id']
     */    
    public function capture(Transaction $transaction, $amount, Payment $payment = null)
    {
        try
        {
			$response = IpagApi::capture($transaction, $amount);

            if(
                isset($response->success) && 
                $response->success && 
                isset($response->data) && 
                $response->data->attributes->status->message == 'CAPTURED'
            ){
                $statusMessage = $response->data->attributes->status->message;

				return array (
					'success' 		 => true,
					'captured' 		 => $statusMessage == 'CAPTURED' ? true : false,
					'paid' 			 => $statusMessage == 'CAPTURED' ? true : false,
					'status' 		 => $statusMessage == 'CAPTURED' ? 'paid' : '',
					'transaction_id' => (string)$response->data->id
				);

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
            Log::error($th->__toString());

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
     * Refund a charge that has previously been created with split rules
     *
     * @param Transaction   $transaction
     * @param Payment       $payment 
     * 
     * @return Array ['success', 'status', 'transaction_id']
     */       
    public function refundWithSplit(Transaction $transaction, Payment $payment)
    {
        try
        {
			return $this->refund($transaction, $payment);

		} catch (\Throwable $ex) {
			Log::error($ex->__toString());

            return array(
                "success" 			=> false ,
                "type" 				=> 'api_refund_error' ,
                "code" 				=> 'api_refund_error',
                "message" 			=> $ex->getMessage(),
				"transaction_id" 	=> ''
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
		try
        {
			$response = IpagApi::refund($transaction);

			if(
                isset($response->success) && 
                $response->success && 
                isset($response->data) && 
                $response->data->attributes->status->message == 'CANCELED'
            ){
                $result = array(
                    "success"           =>  true ,
                    "status"            =>  'refunded',
                    "transaction_id"    =>  (string)$response->data->id                   
                );
                
                return $result;
            }
		
		} catch (\Throwable $ex) {
			Log::error($ex->__toString());

            return array(
                "success" 			=> false ,
                "type" 				=> 'api_refund_error' ,
                "code" 				=> 'api_refund_error',
                "message" 			=> $ex->getMessage(),
				"transaction_id" 	=> ''
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
        try
        {
            $response = IpagApi::retrieve($transaction);

            if(
                isset($response->success) && 
                $response->success && 
                isset($response->data) && 
                isset($response->data->attributes->status->code)
            ){
                return array(
                    'success' 			=> true,
                    'transaction_id' 	=> (string)$response->data->id,
                    'amount' 			=> $response->data->attributes->amount,
                    'destination' 		=> '',	
                    'status' 			=> $this->getStatusString($response->data->attributes->status->code),
                    'card_last_digits' 	=> $payment ? $payment->last_four : ''
                );
            }
            else
            {
                Log::error($response->message);

                return array(
                    "success" 			=> false ,
                    "type" 				=> 'api_retrieve_error' ,
                    "code" 				=> 'api_retrieve_error',
                    "message" 			=> $response->message
                );
            }
        } catch (\Throwable $th) {
            Log::error($th->__toString());

            return array(
                "success" 			=>  false ,
                "type" 				=>  'api_refund_error' ,
                "code" 				=>  'api_refund_error',
                "message" 			=>  $th->getMessage(),
				"transaction_id" 	=>  ''
			);
        }
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
            'gateway'       => 'ipag'
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
        try
        {
            $newAccount = IpagApi::createOrUpdateAccount($ledgerBankAccount);

            if($newAccount->success && isset($newAccount->data->id))
            {
                $ledgerBankAccount->recipient_id = $newAccount->data->id;
                $ledgerBankAccount->save();
                $result = array(
                    'success'       =>  true,
                    'recipient_id'  =>  $ledgerBankAccount->recipient_id
                );
            }
            else
            {
                $result = array(
                    'success'       =>  false,
                    'recipient_id'  =>  ""
                );
            }

            return $result;

        } catch (\Throwable $ex) {
            Log::error($ex->__toString());

			$result = array(
				"success"               =>  false ,
				"recipient_id"          =>  'empty',
				"type"                  =>  'api_bankaccount_error' ,
				"code"                  =>  500 ,
				"message"               =>  trans("empty.".$ex->getMessage())
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
        return 0.5;
    }

    /**
     *  Return a gateway tax
     * 
     * @return Decimal
     */      
    public function getGatewayTax()
    {
        return 0.5;
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
            if(Settings::findByKey('auto_transfer_provider_payment') == "1")
                return(true);
            else
                return(false);
        }
        catch(Exception $ex)
        {
            Log::error($ex);

            return(false);
        }
    }

    //finish
    public function debit(Payment $payment, $amount, $description)
    {
        Log::error('debit_not_implemented');

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
        Log::error('debit_split_not_implemented');

        return array(
            "success" 			=> false,
            "type" 				=> 'api_debit_error',
            "code" 				=> 'api_debit_error',
            "message" 			=> 'split_not_implementd',
            "transaction_id" 	=> ''
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
            case self::CODE_CREATED:
            case self::CODE_IN_ANALISYS:
            case self::CODE_PARTIAL_CAPTURED:
            case self::CODE_IN_DISPUTE:
                return self::PAYMENT_NOTFINISHED;
            case self::CODE_PRE_AUTHORIZED:
                return self::PAYMENT_AUTHORIZED;
            case self::CODE_CAPTURED:
                return self::PAYMENT_PAID;
            case self::CODE_DECLINED:
                return self::PAYMENT_DENIED;
            case self::CODE_CANCELED:
                return self::PAYMENT_VOIDED;
            case self::CODE_CHARGEDBACK:
                return self::PAYMENT_REFUNDED;
            case self::CODE_WAITING_PAYMENT:
                return self::PAYMENT_PENDING;
            default:
                return 'not_geted';
        }
    }

    public function pixCharge($amount, User $user)
    {
        try
        {
            $response = IpagApi::pixCharge($amount, $user);

            if (
                isset($response->success) ||
                $response->success ||
                isset($response->data->id)
            )
                return array (
                    'success'                   =>  true,
                    'captured'                  =>  true,
                    'paid'                      =>  false,
                    'status'                    =>  self::WAITING_PAYMENT,
                    'transaction_id'            =>  (string)$response->data->id,
                    'billet_expiration_date'    =>  $response->data->attributes->pix->qrcode
                );
            else
                return array(
                    "success" 				=>  false,
                    "type" 					=>  'api_charge_error',
                    "code" 					=>  '',
                    "message" 				=>  '',
                    "transaction_id"		=>  '',
                    'billet_expiration_date'=>  ''
                );

        } catch (\Throwable $th) {
            Log::error($th->getMessage());

			return array(
				"success" 				=>  false,
				"type" 					=>  'api_charge_error',
				"code" 					=>  '',
				"message" 				=>  $th->getMessage(),
				"transaction_id"		=>  '',
                'billet_expiration_date'=>  ''
			);
        }
    }

    // /**
    //  * Creates a new transaction when value updates
    //  *
    //  * @param Transaction   $transaction
    //  * @param String        $description        An arbitrary string which you can attach to describe a Charge object
    //  * @param Payment|null  $payment
    //  * @param Boolean       $split              When true, split transaction value on gateway.
    //  * 
    //  * @return Array ['success', 'status', 'captured', 'paid', 'transaction_id']
    //  */    
    // private function chargeToCapture(Transaction $transaction, $description, $payment, $split)
    // {
    //     try
    //     {
    //         $request = Requests::find($transaction->request_id);
    //         $payment = $payment ?? Payment::findDefaultOrFirstByUserId($request->user_id);
    //         $provider = Provider::find($request->confirmed_provider);

    //         if(
    //             $request && 
    //             isset($request->total) && 
    //             $request->total != $request->estimate_price && 
    //             $payment &&
    //             $provider
    //         )
    //         {
    //             $charge = $request->getCharge();
    //             $responseRefund = $this->refund($transaction, $payment);

    //             $this->checkException($responseRefund, 'api_refundrecharge_error');

    //             if($split)
    //                 $chargeResponse = $this->chargeWithSplit($payment, $provider, $request->total, $request->provider_commission, $description, false, null);
    //             else
    //                 $chargeResponse = $this->charge($payment, $request->total, $description, false, null);

    //             $this->checkException($chargeResponse, 'api_recharge_error');

    //             RequestCharging::createCardTransactionUpdateRequest($chargeResponse, $charge, $request, false);

    //             return $chargeResponse;
    //         }

    //         $this->checkException(["success" => false], 'api_recharge_params_error');

    //     } catch (\Throwable $th) {
    //         Log::error($th->getMessage());

	// 		return array(
	// 			"success" 				=>  false,
	// 			"type" 					=>  'api_chargecapture_error',
	// 			"code" 					=>  'api_chargecapture_error',
	// 			"message" 				=>  trans('creditCard.try_charge_fail_message'),
	// 			"transaction_id"		=>  '',
    //             'billet_expiration_date'=>  ''
	// 		);
    //     }
    // }

    /**
     * In the absence of a positive response, creates an exception with error response
     *
     * @param Array         $response           Array corresponding a gateway responses
     * @param String        $errorMessage       An arbitrary string which you can attach to describe a fail
     * 
     * @return void
     */  
    private function checkException($response, $errorMessage)
    {
        if(!isset($response['success']) || !$response['success'])
            throw new Exception($errorMessage);
    }
}
