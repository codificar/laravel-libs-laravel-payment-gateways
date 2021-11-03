<?php 

use Carbon\Carbon;

class ByeBankLib implements IPayment
{

    const PAYMENT_TYPE_CARD  = 'card';

    const BYEBNK_API_KEY = 'byebnk_api_key';
    const BYEBNK_API_USER = 'byebnk_api_user';

    const AUTO_TRANSFER_PROVIDER = 'auto_transfer_provider_payment';

    private $token;
    private $clientId;

    public function __construct()
    {
        $this->setApiKey();
    }

    private function setApiKey()
    {
        $this->token = Settings::findByKey(self::BYEBNK_API_KEY);
        $this->clientId = Settings::findByKey(self::BYEBNK_API_USER);
    }

    public function charge(Payment $payment, $amount, $description, $capture = true, $user = null)
    {   
        try
        {
            $amount = floor($amount * 100);

            $response = ByeBankApi::pay($this->clientId, $this->token, $payment->customer_id, $amount, $description, self::PAYMENT_TYPE_CARD, $payment->card_token, null, $capture);

            if(!$response['success'])
            {
                \Log::error($response['message']);

                return array(
                    "success" 					=> false ,
                    "type" 						=> 'api_charge_error',
                    "code" 						=> 'api_charge_error',
                    "message" 					=> $response['message'],
                    "transaction_id"			=> ''
                );                
            }

			return array (
				'success' => true,
				'captured' => $capture,
				'paid' => $capture,
				'status' => $capture ? 'paid' : 'authorized',
				'transaction_id' => $response['paymentId']
			);
        }
        catch(Exception $ex)
        {
            \Log::error($ex->getMessage());

            return array(
                "success" 					=> false ,
                "type" 						=> 'api_charge_error' ,
                "code" 						=> 'api_charge_error' ,
                "message" 					=> trans("paymentError.".$ex->getMessage()),
                "transaction_id"			=> ''
            );		
        }        
    }

    public function chargeWithSplit(Payment $payment, Provider $provider, $totalAmount, $providerAmount, $description, $capture = true, User $user = null)
    {
        $response = $this->charge($payment, $totalAmount, $description, $capture, $user);
        
        return $response;
    }
   
    public function capture(Transaction $transaction, $amount, Payment $payment)
    {
        try
        {
            $amount = floor($amount * 100);

            $response = ByeBankApi::capture($this->clientId, $this->token, $payment->customer_id, $transaction->gateway_transaction_id, $amount);

            if(!$response['success'])
            {
                \Log::error($response['message']);

                return array(
                    "success" 					=> false ,
                    "type" 						=> 'api_capture_error',
                    "code" 						=> 'api_capture_error',
                    "message" 					=> $response['message'],
                    "transaction_id"			=> $transaction->gateway_transaction_id
                );                
            }            

            return array(
                "success" 			=> true ,
                "status" 			=> 'paid',
				"captured"          => true,
				"paid"              => true,                
                "transaction_id" 	=> $transaction->gateway_transaction_id
            );
            
        } 
        catch (Exception $ex) {
  
            \Log::error($ex->__toString());

            return array(
                "success" 			=> false ,
                "type" 				=> 'api_capture_error' ,
                "code" 				=> 'api_capture_error',
                "message" 			=> $ex->getMessage(),
                "transaction_id" 	=> $transaction->gateway_transaction_id
            );
       }        
    }

    public function captureWithSplit(Transaction $transaction, Provider $provider, $totalAmount, $providerAmount, Payment $payment)
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
        try
        {
            $response = ByeBankApi::payback($this->clientId, $this->token, $payment->customer_id, $transaction->gateway_transaction_id);

            if(!$response['success'])
            {
                \Log::error($response['message']);

                return array(
                    "success" 					=> false ,
                    "type" 						=> 'api_refund_error',
                    "code" 						=> 'api_refund_error',
                    "message" 					=> $response['message']
                );                
            }            

            return array(
                "success" 			=> true ,
                "status" 			=> 'refunded' ,
                "transaction_id" 	=> $transaction->gateway_transaction_id ,
            );
            
        } 
        catch (Exception $ex) {
  
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

    /*
     * @return Array ['success', 'transaction_id', 'amount', 'destination', 'status', 'card_last_digits']
     */       
    public function retrieve(Transaction $transaction, Payment $payment){

        $response = ByeBankApi::retrieve($this->clientId, $this->token, $payment->customer_id, $transaction->gateway_transaction_id);

        if(!$response['success'])
        {
            \Log::error($response['message']);

            return array(
                "success" 			=> false ,
                "type" 				=> 'api_retrieve_error' ,
                "code" 				=> 'api_retrieve_error',
                "message" 			=> $response['message']
            );            
        }

		return array(
			'success' => true,
			'transaction_id' => $response['id'],
			'amount' => $response['amount_cents'],
			'destination' => '',
			'status' => $this->getStatus($response),
			'card_last_digits' => $payment->last_four,
		);

    }

    /*
     * @return Array ['success', 'token', 'card_token', 'customer_id', 'card_type', 'last_four']
     */      
    public function createCard(Payment $payment, $user = null)
    {
        try
        {
            $cardNumber 			= $payment->getCardNumber();
            $cardExpirationMonth 	= $payment->getCardExpirationMonth();
            $cardExpirationYear 	= $payment->getCardExpirationYear();
            $cardCvv 				= $payment->getCardCvc();
            $cardHolder 			= $payment->getCardHolder();   
            
            if(!$user)
                $user = $payment->User;

			$response = $this->listUser($user);
			
			\Log::debug('[createCard]responselistUser:'. json_encode($response));

            if(!$response['success'] || $response['userId'] == null)
                $customer_id = $this->createUser($user);    
            else
                $customer_id = $response["userId"]; 
                
            if($cardExpirationYear / 100 < 1)
                $cardExpirationYear += 2000;

            $cardHolder = $this->removeSpecialCharacter($cardHolder);
                
            if(!$customer_id)
            {
                \Log::error('[createCard]customer_id is null');

                return array(
                    "success" 					=> false ,
                    "type" 						=> 'api_create_card_error',
                    "code" 						=> 'api_create_card_error',
                    "message" 					=> 'customer_id is null'
                );                 
            }

            $response = ByeBankApi::createCard($this->clientId, $this->token, $customer_id, $cardNumber, $cardHolder, $cardExpirationMonth,$cardExpirationYear, $cardCvv);

            if(!$response['success'])
            {
                \Log::error($response['message']);

                return array(
                    "success" 					=> false ,
                    "type" 						=> 'api_create_card_error',
                    "code" 						=> 'api_create_card_error',
                    "message" 					=> $response['message']
                );                
            } 

            return array(
                "success" 					=> true ,
                "token" 					=> $response['card_id'] ,
                "card_token" 				=> $response['card_id'] ,
                "customer_id" 				=> $customer_id ,
                "card_type" 				=> $this->getCreditCardType($cardNumber),
                "last_four" 				=> $response['last_digit'] 
            );

		}
		catch(Exception  $ex){
			
			\Log::error($ex->getMessage());

			return array(
				"success" 					=> false ,
				"type" 						=> 'api_create_card_error' ,
				"code" 						=> 'api_create_card_error' ,
				"message" 					=> trans("paymentError.api_create_card_error") ,
			);
		}            
    }

    public function deleteCard(Payment $payment, User $user)
    {
        try
        {
            $response = ByeBankApi::deleteCard($this->clientId, $this->token, $payment->customer_id, $payment->card_token);

            if(!$response['success'])
            {
                \Log::error($response['message']);

                return array(
                    "success"   => false ,
                    "error"     => array(
                        "code" 		=> 'api_deleteCard_error',
                        "messages" 	=> $response['message']
                    )                
                );           
            }

            return array(
                "success" 					=> true 
            );  
        }
		catch(Exception  $ex){
			
			\Log::error($ex->getMessage());

			return array(
                "success"   => false ,
				"error"     => array(
                    "code" 		=> 'api_deleteCard_error',
                    "messages" 	=> $ex->getMessage()
				)                
			);
		}             
    }

	/*
		* @return Array ['success', 'recipient_id']
		*/      
	public function createOrUpdateAccount(LedgerBankAccount $ledgerBankAccount)
	{
		return array(
			'success' => true,
			'recipient_id' => '',
		);
	}    

	private function getUserAddress($user){

		$zipCode = str_replace('.', '', str_replace('-', '', $user->getZipcode()));
		$zipCode = trim(preg_replace('/\s\s+/', '', $zipCode));

		return array(
            "street_address" 	=> $user->getStreet(),
            "complement"        => $user->getStreetNumber(),
            "additional_info"   => null,
            "neighborhood" 	    => $user->getNeighborhood(),
            "city" 	            => $user->address_city,
            "state"             => $user->state,
            "postal_code" 		=> $zipCode,
            "country_code" 		=> "BR"
            
        );
	}

	private function createUser($user)
	{
		$document = str_replace('.', '', str_replace('-', '', $user->document));
		$document = trim(preg_replace('/\s\s+/', '', $document));

		$address =$this->getUserAddress($user);

		$result = ByeBankApi::createUser($this->clientId, $this->token, $user->first_name, $user->last_name, $document, $user->email, $user->phone, $user->id, $address);

		if(!$result['success'])
			return(null);

		return($result['userId']);
	}

    private function listUser($user)
    {
        $document = str_replace('.', '', str_replace('-', '', $user->document));
        $document = trim(preg_replace('/\s\s+/', '', $document));

        $result = ByeBankApi::listUser($this->clientId, $this->token, $document, $user->email);

        return($result);
    }   
    
	private function getStatus($byebnkTransaction)
	{

        switch($byebnkTransaction['status'])
        {
            case ByeBankApi::STATUS_PENDING:
                return('processing');

            case ByeBankApi::STATUS_FINISHED:
            case ByeBankApi::STATUS_SUCCEEDED:
                if($byebnkTransaction['capture'])
                    return('paid');
                else
                    return('authorized');

            case ByeBankApi::STATUS_CANCELED:
                return('refunded');

            default:
                return('error');
        }
    }   
    
    private function getCreditCardType($str, $format = 'string')
    {
        if (empty($str)) {
            return false;
        }

        $matchingPatterns = [
            'visa' => '/^4[0-9]{12}(?:[0-9]{3})?$/',
            'mastercard' => '/^5[1-5][0-9]{14}$/',
            'amex' => '/^3[47][0-9]{13}$/',
            'diners' => '/^3(?:0[0-5]|[68][0-9])[0-9]{11}$/',
            'discover' => '/^6(?:011|5[0-9]{2})[0-9]{12}$/',
            'jcb' => '/^(?:2131|1800|35\d{3})\d{11}$/',
            'elo' => '/^((((636368)|(438935)|(504175)|(451416)|(636297))\d{0,10})|((5067)|(4576)|(4011))\d{0,12})$/',
            'any' => '/^(?:4[0-9]{12}(?:[0-9]{3})?|5[1-5][0-9]{14}|6(?:011|5[0-9][0-9])[0-9]{12}|3[47][0-9]{13}|3(?:0[0-5]|[68][0-9])[0-9]{11}|(?:2131|1800|35\d{3})\d{11})$/'
        ];

        $ctr = 1;
        foreach ($matchingPatterns as $key=>$pattern) {
            if (preg_match($pattern, $str)) {
                return $format == 'string' ? $key : $ctr;
            }
            $ctr++;
        }
    }

    private function removeSpecialCharacter($val)
    {
        $val = json_decode('"'.str_replace('"', '\"',$val).'"');
        $val = preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/","/(ç)/","/(Ç)/"),explode(" ","a A e E i I o O u U n N c C"),$val);
    
        return($val);
    }

    public function getGatewayFee()
    {
        return 0.5;
    }

    public function getGatewayTax()
    {
        return 0.0399 ;
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

}

?>