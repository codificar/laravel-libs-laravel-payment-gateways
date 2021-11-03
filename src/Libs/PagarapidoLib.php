<?php

namespace Codificar\PaymentGateways\Libs;
use Carbon\Carbon;

use Codificar\PaymentGateways\Libs\PagarapidoApi;

use ApiErrors;
//models do sistema
use Payment;
use Provider;
use Transaction;
use User;
use LedgerBankAccount;
use Settings;

Class PagarapidoLib implements IPayment
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

        try {
            $expirationYear = (String)$payment->getCardExpirationYear();
            if(strlen($expirationYear) == 4) {
                $expirationYear = $expirationYear[2] . $expirationYear[3];
            }

            $expirationMonth =  (String)$payment->getCardExpirationMonth();
            if(strlen($expirationYear) == 1) {
                $expirationMonth = "0" . $expirationMonth[0];
            }

            $user = User::find($payment->user_id);

            $userDoc = trim($this->cleanWord($user->getDocument()));
            $docType = strlen($userDoc) <= 11 ? 'private' : 'legal';

            $gatewayKey = Settings::findByKey('pagarapido_gateway_key');

            $isProd = Settings::findByKey('pagarapido_production');
            $isProd = (int)$isProd ? true : false;
            $pagarapido = new PagarapidoApi($gatewayKey, $isProd);

            $transaction = $pagarapido->transactionCard(array(
                'installments' => 1, //optional, default 1
                'cardNumber' => $payment->getCardNumber(),
                'cardCvv' => $payment->getCardCvc(),
                'cardExpirationYear' => $expirationYear,
                'cardExpirationMonth' => $expirationMonth,
                'cardHolderName' =>  $payment->getCardHolder(),
                'amount' => $amount,
                'customer' => [
                    'name' => $user->getFullName(),
                    'document' => $userDoc,
                    'type' => $docType, //cpf 'private' - cnpj 'legal'
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'addresses' => [
                        'city' => $user->address_city,
                        'neighborhood' => $user->address_neighbour,
                        'number' => $user->address_number,
                        'postalCode' => $user->zipcode,
                        'state' => $user->state,
                        'street' => $user->address
                    ]
                ]
            ));
            \Log::info("Pagarapido charge response: " . print_r($transaction, true));
			if ($transaction['success'] && $transaction['data']->_id && $transaction['data']->status == 'payment_accepted') {
				$result = array (
                    'success' => true,
                    'captured' => true,
                    'paid' => true,
                    'status' => 'paid',
                    'transaction_id' => $transaction['data']->_id 
                );
				return $result;
			} else {
                \Log::error('Error Pagarapido charge 1');
                return array(
                    "success" 					=> false ,
                    "type" 						=> 'api_charge_error' ,
                    "code" 						=> 'paymentError',
                    "message" 					=> "paymentError",
                    "transaction_id"			=> ''
                );
            }
		} catch (Exception $th) {
			\Log::error('Error Pagarapido charge 2');
			return array(
                "success" 					=> false ,
				"type" 						=> 'api_charge_error' ,
				"code" 						=> $th->getMessage(),
				"message" 					=> "paymentError",
				"transaction_id"			=> ''
            );
            
		}
    }

    public function billetCharge($amount, $client, $postbackUrl = null, $billetExpirationDate, $billetInstructions)
    {
        try {
            $gatewayKey = Settings::findByKey('pagarapido_gateway_key');
            $isProd = Settings::findByKey('pagarapido_production');
            $isProd = (int)$isProd ? true : false;
            $pagarapido = new PagarapidoApi($gatewayKey, $isProd);

            $clientDoc = trim($this->cleanWord($client->getDocument()));
            $docType = strlen($clientDoc) <= 11 ? 'private' : 'legal';

            $transaction = $pagarapido->transactionBoleto(array(
                'amount' => $amount,
                'dueDate' => $billetExpirationDate,
                'returnUrl' => '',
                'customer' => [
                    'name' => $client->getFullName(),
                    'document' => $clientDoc,
                    'type' => $docType, //cpf 'private' - cnpj 'legal'
                    'email' => $client->email,
                    'phone' => $client->phone,
                    'addresses' => [
                        'city' => $client->address_city,
                        'neighborhood' => $client->address_neighbour,
                        'number' => $client->address_number,
                        'postalCode' => $client->zipcode,
                        'state' => $client->state,
                        'street' => $client->address
                    ]
                ]
            ));
            \Log::info("Pagarapido billetCharge response: " . print_r($transaction, true));

            if ($transaction['success'] && $transaction['data']->_id && 
                ($transaction['data']->status == 'awaiting_payment' || $transaction['data']->status == 'awaiting_confirmation_payment')
            ) {
				return array (                    
                    'success' => true,
                    'captured' => true,
                    'paid' => true,
                    'status' => 'waiting_payment',
                    'transaction_id' => $transaction['data']->_id,
                    'billet_url' => '',
                    'digitable_line' => '123',
                    'billet_expiration_date' => $billetExpirationDate
                );
			} else {
                \Log::error('Error Pagarapido billet 1');
                return array(
                    "success" 				=> false ,
                    "type" 					=> 'api_charge_error' ,
                    "code" 					=> '',
                    "message" 				=> $th->getMessage(),
                    "transaction_id"		=> ''
                );
            }

        } catch (\Throwable $th) {
            \Log::error('Error Pagarapido billet 2');
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
        
		try {
            $gatewayKey = Settings::findByKey('pagarapido_gateway_key');
            $gatewayLogin = Settings::findByKey('pagarapido_login');
            $gatewayPassword = Settings::findByKey('pagarapido_password');

            $isProd = Settings::findByKey('pagarapido_production');
            $isProd = (int)$isProd ? true : false;
            $pagarapido = new PagarapidoApi($gatewayKey, $isProd);

            //For this request the token is required. It can be obtained at login
            $login = $pagarapido->login($gatewayLogin, $gatewayPassword);
            if(!$login['success']) {
                \Log::error("credenciais de login do pagarapido invalidas");
                $refund['success'] = false;
            } else {
                $pagarapido->setToken($login['data']->token);
                $refund = $pagarapido->cancelTransaction($transaction->gateway_transaction_id);
            }
            
            \Log::info("Pagarapido refund response: " . print_r($refund, true));
			if ($refund['success']) {
				return array(
					"success" 			=> true ,
					"status" 			=> 'refunded',
					"transaction_id" 	=> $transaction->gateway_transaction_id,
				);
			} else {
                \Log::error('Error Pagarapido refund 1');
                return array(
                    "success" 			=> false ,
                    "type" 				=> 'api_refund_error' ,
                    "code" 				=> 'api_refund_error',
                    "message" 			=> 'api_refund_error',
                    "transaction_id" 	=> $transaction->gateway_transaction_id
                );
            }
		} catch (Exception $th) {
			\Log::error('Error Pagarapido refund 2 ');
			return array(
                "success" 					=> false ,
				"type" 						=> 'api_refund_error' ,
				"code" 						=> $th->getMessage(),
				"message" 					=> "api_refund_error",
				"transaction_id"			=> $transaction->gateway_transaction_id
            );
            
		}
    }
      
    public function retrieve(Transaction $transaction, Payment $payment = null)
    {
        try {
            $gatewayKey = Settings::findByKey('pagarapido_gateway_key');
            $isProd = Settings::findByKey('pagarapido_production');
            $isProd = (int)$isProd ? true : false;
            $pagarapido = new PagarapidoApi($gatewayKey, $isProd);
            $getTransaction = $pagarapido->getTransactionCard($transaction->gateway_transaction_id);
            \Log::info("Pagarapido retrieve response: " . print_r($getTransaction, true));
            if($getTransaction['success'] && $getTransaction['data'] && $getTransaction['data']->status){
                switch ($getTransaction['data']->status) {
                    case 'payment_accepted':
                        $status = 'paid';
                        break;
                    case 'payment_canceled':
                        $status = 'refunded';
                        break;
                    case 'payment_rejected':
                        $status = 'refused';
                        break;
                    case 'awaiting_payment':
                        $status = 'waiting_payment';
                        break;
                    case 'awaiting_confirmation_payment':
                        $status = 'waiting_payment';
                        break;
                    default:
                        $status = 'error';
                }
                
                return array(
                    'success' 			=> true,
                    'transaction_id' 	=> $transaction->gateway_transaction_id,
                    'amount' 			=> $getTransaction['data']->amount,
                    'destination' 		=> '',	
                    'status' 			=> $status,
                    'card_last_digits' 	=> $payment ? $payment->last_four : ''
                );
            } else {
                \Log::error('Error Pagarapido retrieve 1');
                return array(
                    "success" 			=> false ,
                    "type" 				=> 'api_retrieve_error' ,
                    "code" 				=> 'api_retrieve_error',
                    "message" 			=> 'api_retrieve_error'
                );  
            }

        } catch (\Throwable $th) {
            \Log::error('Error Pagarapido retrieve 2');
            return array(
                "success" 				=> false ,
                "type" 					=> 'api_retrieve_error' ,
                "code" 					=> '',
                "message" 				=> $th->getMessage()
            );
        }

		
    }
     
    public function createCard(Payment $payment, User $user = null)
    {
        $cardNumber = $payment->getCardNumber();

		$result = array(
			'success'		=>	true,
			'customer_id'	=>	'',
			'last_four'		=>	substr($cardNumber, -4),
			'card_type'		=>	detectCardType($cardNumber),
            'card_token'	=>	'',
            'token'	        =>	'',
            'gateway'       => 'pagarapido'
		);

		return $result;
    }
    
    public function deleteCard(Payment $payment, User $user = null){
        $result = array (
			'success'	=>	true
		);
		return $result;
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

    public function pixCharge($holder, $amount)
    {
        \Log::error('pix_not_implemented');
        return array(
            "success" 			=> false,
            "qr_code_base64"    => '',
            "copy_and_paste"    => '',
            "transaction_id" 	=> ''
        );
    }

    private function cleanWord($word)
	{
		$word = str_replace(".", "", $word);
		$word = str_replace("-", "", $word);
		$word = str_replace("/", "", $word);
		$word = str_replace("/n", "", $word);

		return $word;
	}
}