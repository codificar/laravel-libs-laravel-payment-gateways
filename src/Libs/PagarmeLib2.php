<?php

namespace Codificar\PaymentGateways\Libs;

use ApiErrors;
use Bank;
use Carbon\Carbon;
use Codificar\PaymentGateways\Libs\handle\phone\PhoneNumber;
use Exception;
use PagarMe\Client as PagarMe;
use PagarMe\Exceptions\PagarMeException as PagarMe_Exception;
//models do sistema
use Payment;
use Provider;
use Transaction;
use User;
use LedgerBankAccount;
use Settings;
use RequestMeta;
use Requests;

class PagarmeLib2 implements IPayment
{
    const SPLIT_TYPE_AMOUNT 		= 'amount';
    const SPLIT_TYPE_PERCENTAGE 	= 'percentage';

    const PAGARME_PAID 				= 'paid';
    const PAGARME_PROCESSING 		= 'processing';
    const PAGARME_AUTHORIZED 		= 'authorized';
    const PAGARME_REFUNDED 			= 'refunded';
    const PAGARME_WAITING 			= 'waiting_payment';
    const PAGARME_PENDING_REFUND 	= 'pending_refund';
    const PAGARME_REFUSED 			= 'refused';
	const PAGARME_ERROR 			= 'error';

    const AUTO_TRANSFER_PROVIDER = 'auto_transfer_provider_payment';

    public function __construct()
    {
        $this->setApiKey();
    }

    private function setApiKey()
    {
       return $pagarme = new PagarMe(Settings::findByKey('pagarme_api_key'));
    }

    public function createCard(Payment $payment, User $user = null)
    {
        try {
            $cardNumber 			= $payment->getCardNumber();
            $cardExpirationMonth 	= $payment->getCardExpirationMonth();
            $cardExpirationYear 	= $payment->getCardExpirationYear();
            $cardCvv 				= $payment->getCardCvc();
            $cardHolder 			= $payment->getCardHolder();
            $expirationDate         = str_pad($cardExpirationMonth, 2, '0', STR_PAD_LEFT) . str_pad($cardExpirationYear, 2, '0', STR_PAD_LEFT);

            $cardExpirationYear = $cardExpirationYear % 100;

            $card = $pagarme->cards()->create([
                'holder_name' => $cardHolder,
                'number' => $cardNumber ,
                'expiration_date' => $expirationDate,
                'cvv' => $cardCvv,
                // "card_number" 				=> $cardNumber,
                // "card_holder_name" 			=> $cardHolder,
                // "card_expiration_month" 	=> str_pad($cardExpirationMonth, 2, '0', STR_PAD_LEFT),
                // "card_expiration_year" 		=> str_pad($cardExpirationYear, 2, '0', STR_PAD_LEFT),
                // "card_cvv" 					=> $cardCvv,
            ]);

            return array(
                "success" 					=> true,
                "token" 					=> $card->id,
                "card_token" 				=> $card->id,
                "customer_id" 				=> $card->id,
                "card_type" 				=> strtolower($card->brand),
                "last_four" 				=> $card->last_digits,
                "gateway"					=> "pagarme"
            );
        } catch (PagarMe_Exception  $ex) {
            \Log::error($ex->getMessage());

            return array(
                "success" 					=> false,
                "type" 						=> $ex->getMessage(),
                "code" 						=> $ex->getReturnCode(),
                "message" 					=> $ex->getReturnCode() ? trans("paymentError." . $ex->getReturnCode()) : $ex->getMessage(),
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
    public function billetCharge($amount, $client, $postbackUrl, $billetExpirationDate, $billetInstructions = "")
    {
        try {
            $pagarme = self::setApiKey();

            $pagarMeTransaction = $pagarme->transactions()->create(array(
                "amount" => floor($amount * 100),
                "payment_method" => "boleto",
                "postback_url" => $postbackUrl,
                "async" => false,
                "capture" => true,
                "customer" => $this->getBilletCustomer($client),
                "boleto_expiration_date" => $billetExpirationDate,
                "boleto_instructions" => $billetInstructions
            ));
            
            return array(
                'success' => true,
                'captured' => true,
                'paid' => ($pagarMeTransaction->status == self::PAGARME_PAID),
                'status' => $pagarMeTransaction->status,
                'transaction_id' => $pagarMeTransaction->id,
                'billet_url' => $pagarMeTransaction->boleto_url,
                'digitable_line' => $pagarMeTransaction->boleto_barcode,
                'billet_expiration_date' => $pagarMeTransaction->boleto_expiration_date
            );
        } catch (PagarMe_Exception $ex) {
            \Log::error($ex->getMessage());

            return array(
                "success" 				=> false ,
                "type" 					=> 'api_charge_error' ,
                "code" 					=> $ex->getReturnCode() ,
                "message" 				=> trans("paymentError.".$ex->getReturnCode()) ,
                "transaction_id"		=> ''
            );
        }
    }

    /**
     * Trata o postback retornado pelo gateway
     * @param object $request
     * @return array
     */
    public function billetVerify($request, $transaction_id = null)
    {

        //If has transaction id, retrieve and check the billet status
        if ($transaction_id) {
            $transaction = Transaction::find($transaction_id);
            $retrieve = $this->retrieve($transaction);
            return [
                'success' => true,
                'status' => $retrieve['status'],
                'transaction_id' => $retrieve['transaction_id']
            ];
        } else {
            $postbackTransaction = $request->transaction;
        
            if (!$postbackTransaction) {
                return [
                    'success' => false,
                    'status' => '',
                    'transaction_id' => ''
                ];
            }
    
            return [
                'success' => true,
                'status' => $postbackTransaction['status'],
                'transaction_id' => $postbackTransaction['id']
            ];
        }
    }

    /**
     * Teste de pagamento do boleto
     */
    public function testBilletPaid($transaction_id)
    {
        $pagarme = self::setApiKey();
        $transaction =$pagarme->search()->get([                
            "type" => "transaction",
            "query" => [
                "query" => [
                    "terms" => [
                        "items.id" => [$transaction_id] // Busca transações do ID
                    ]
                ]
            ]
        ]);

        if ($transaction) {
            $transaction->setStatus('paid');
            $transaction->save();
            return true;
        }

        return false;
    }

    //realiza cobrança no cartão do usuário sem repassar valor algum ao prestador
    public function charge(Payment $payment, $amount, $description, $capture = true, User $user = null)
    {
        try {
            $pagarme = self::setApiKey();
            // valor inteiro do pagamento transferido para o admin
            $card = $pagarme->cards()->get([
                'id' => $payment->card_token
            ]);
            if ($card == null) {
                throw new PagarMe_Exception("not_found","card","Cartão não encontrado");
            } 
            $pagarMeTransaction = $pagarme->transactions()->create(array(
                "amount" 	=> 	floor($amount * 100),
                "async"		=>  false,
                "card_id" 	=> 	$payment->card_token,
                "capture" 	=> 	boolval($capture),
                "customer" 	=> 	$this->getCustomer($payment),
                "billing"	=> 	$this->getBilling($payment->id),
                "items"		=>  $this->getItems(1, $description, floor($amount * 100)),
                "card"      =>  $card
            ));

            $pagarJson = json_decode(json_encode($pagarMeTransaction));
            $json = json_encode($pagarJson);

            \Log::debug('JsonPagarme: '. print_r($pagarJson, 1));

            \Log::debug("[charge]response:". print_r($pagarMeTransaction, 1));

			if ($pagarMeTransaction->status == self::PAGARME_REFUSED) {
				return array(
					"success" 					=> false,
					"type" 						=> 'api_charge_error',
					"code" 						=> 'api_charge_error',
					"message" 					=> trans("paymentError.refused"),
					"transaction_id"			=> $pagarMeTransaction->id,
					'status' 					=> self::PAGARME_ERROR,
				);
			}

			return array (
				'success' 			=> true,
				'captured' 			=> $capture,
				'paid' 				=> ($pagarMeTransaction->status == self::PAGARME_PAID),
				'status' 			=> self::PAGARME_ERROR,
				'transaction_id' 	=> strval($pagarMeTransaction->id)
			);
		}
		catch(PagarMe_Exception $ex)
		{
			\Log::error($ex->getMessage().$ex->getTraceAsString());

			return array(
				"success" 					=> false ,
				"type" 						=> 'api_charge_error' ,
				"code" 						=> $ex->getReturnCode() ,
				"message" 					=> trans("paymentError.".$ex->getReturnCode()) ,
				"transaction_id"			=> '',
				"status"					=> 'error'
			);		
		}
	}
	
	//relaliza cobrança no cartão do usuário com repasse ao prestador
	public function chargeWithSplit(Payment $payment, Provider $provider, $totalAmount, $providerAmount, $description, $capture = true, User $user = null){
		
		try
		{

			$admin_value 	= $totalAmount - $providerAmount;
			$admin_value 	= round($admin_value * 100);
			$providerAmount = round($providerAmount * 100);

            if ($admin_value + $providerAmount == (round($totalAmount*100))) {
                $totalAmount =  round($totalAmount*100);
            } elseif ($admin_value + $providerAmount == (ceil($totalAmount*100))) {
                $totalAmount =  ceil($totalAmount*100);
            } elseif ($admin_value + $providerAmount == (floor($totalAmount*100))) {
                $totalAmount =  floor($totalAmount*100);
            }

            if (PagarMe_Recipient::findById(Settings::findByKey('pagarme_recipient_id')) == null) {
                throw new PagarMe_Exception("Recebedor do Administrador não foi encontrado. Corrigir no sistema Web.", 1);
            }

            $bank_account = LedgerBankAccount::where("provider_id", "=", $provider->id)->first();
            
            if ($bank_account == null) {
                throw new PagarMe_Exception("Conta do prestador nao encontrada.", 1);
            }

            $recipient = PagarMe_Recipient::findById($bank_account->recipient_id);

            if ($recipient == null) {
                throw new PagarMe_Exception("Recebedor não foi encontrado", 1);
            }

            $card = PagarMe_Card::findById($payment->card_token);
                    
            if ($card == null) {
                throw new PagarMe_Exception("Cartão não encontrado", 1);
            }

            //split de pagamento com o prestador
            $pagarmeTransaction = new PagarMe_Transaction(array(
                "amount" 		=> 	$totalAmount,
                "async"			=>  false,
                "card_id" 		=> 	$payment->card_token,
                "capture" 		=> 	boolval($capture),
                "customer" 		=> 	$this->getCustomer($payment),
                "billing"		=> 	$this->getBilling($payment->id),
                "items"			=>  $this->getItems(1, $description, $totalAmount),
                "split_rules" 	=> 	array(
                    //prestador
                    array(
                        "recipient_id" 			=> 	$recipient->id,
                        "amount"	 			=>  $providerAmount,
                        "charge_processing_fee" => 	self::getReversedProcessingFeeCharge() ? true : false,
                        "liable" => true  //assume risco de transação (possíveis estornos)
                    ),
                    //admin
                    array(
                        "recipient_id" => Settings::findByKey('pagarme_recipient_id'),
                        "amount" =>  $admin_value,
                        "charge_processing_fee" => self::getReversedProcessingFeeCharge() ? false : true, //responsável pela taxa de processamento
                        "liable" => true  //assume risco da transação (possíveis estornos)
                    )
                )
            ));

            \Log::debug("[charge]parameters:". print_r($pagarmeTransaction, 1));

            $pagarmeTransaction->charge();

            \Log::debug("[charge]response:". print_r($pagarmeTransaction, 1));

            if ($pagarmeTransaction->status == self::PAGARME_REFUSED) {
                return array(
                    "success" 					=> false ,
                    "type" 						=> 'api_charge_error' ,
                    "code" 						=> 'api_charge_error' ,
                    "message" 					=> trans("paymentError.refused") ,
                    "transaction_id"			=> $pagarmeTransaction->id
                );
            }

            return array(
                'success' => true,
                'captured' => $capture,
                'paid' => ($pagarmeTransaction->status == self::PAGARME_PAID),
                'status' => $pagarmeTransaction->status,
                'transaction_id' => $pagarmeTransaction->id
            );
        } catch (PagarMe_Exception $ex) {
            \Log::error($ex->getMessage().$ex->getTraceAsString());

            return array(
                "success" 					=> false ,
                "type" 						=> 'api_charge_error' ,
                "code" 						=> $ex->getReturnCode() ,
                "message" 					=> trans("paymentError.".$ex->getReturnCode()) ,
                "transaction_id"			=> ''
            );
        }
    }
    
    // private function getCustomer(Payment $payment){
    // 	$user = $payment->User ;

    // 	$docLenght = strlen(trim($this->cleanWord($user->document)));
    
    // 	if ($docLenght <= 11) {
    // 		$type = "individual";
    // 		$docType = "cpf";
    // 	} else {
    // 		$type = "corporation";
    // 		$docType = "cnpj";
    // 	}

    // 	$customer = array(
    // 		"name" 				=> $user->getFullName(),
    // 		"documents" 		=> array(
    // 			array(
    // 				'type'			=> $docType,
    // 				'number' 		=> $user->document
    // 			),
                
    // 		),
    // 		"email" 			=> $user->email,
    // 		"external_id"		=> (string) $user->id,
    // 		"type"				=> $type,
    // 		"country"			=> "br",
    // 		"phone_numbers" 	=> array(
                
    // 				"+55".(string) $user->getLongDistance().(string) $user->getPhoneNumber()
    // 		)
    // 	);

    // 		return $customer ;
    // }

    private function getCustomer(Payment $payment)
    {
        $user = $payment->user_id ? $payment->User : $payment->Provider ;

        $docLenght = strlen(trim($this->cleanWord($user->document)));
    
        if ($docLenght <= 11) {
            $type = "individual";
            $docType = "cpf";
        } else {
            $type = "corporation";
            $docType = "cnpj";
        }

        $zipcode = $user->getZipcode();
        $zipcode = $this->cleanWord($zipcode);

        try {
            $phoneLib = new PhoneNumber($user->phone);
        } catch (\Exception $e) {
            \Log::error($e->getMessage() . $e->getTraceAsString());
        }
        $documentNumber = preg_replace( '/[^0-9]/', '', $user->document);

        $customer = array(
            "name" 				=> $user->getFullName(),
            "email" 			=> $user->email,
            "documents" => [
                            [
                                'type'			=> $docType,
                                'number' 		=> $documentNumber
                            ],
                        ],
            "external_id"		=> (string) $user->id,
            "type"				=> $type,
            "country"			=> "br",
            "phone_numbers" 	=> array($phoneLib->getFullPhoneNumber())
        );

        return $customer ;
    }
    
    /**
     * Formata informações do customer para gerar o boleto
     */
    private function getBilletCustomer($user)
    {
        $zipcode = $user->getZipcode();
        $zipcode = $this->cleanWord($zipcode);

        try {
            $phoneLib = new PhoneNumber($user->phone);
        } catch (\Exception $e) {
            \Log::error($e->getMessage() . $e->getTraceAsString());
        }
        $customer = array(
            "name" 				=> $user->getFullName(),
            "email" 			=> $user->email,
            "external_id"		=> (string) $user->id,
            "phone_numbers" 	=> array($phoneLib->getFullPhoneNumber())
        );

        return $customer ;
    }
    public function capture(Transaction $transaction, $amount, Payment $payment = null)
    {
        try {
            $pagarme = self::setApiKey();
            $amount *= 100;

            $pagarMeTransaction =$pagarme->transactions()->capture([
                'id' => $transaction->gateway_transaction_id,
            ]);

            if ($pagarMeTransaction == null) {
                throw new PagarMe_Exception("Transaction not found.","Transaction","Transação não encontrada");
            }

            if ($amount > $pagarMeTransaction->amount) {
                $amount = $pagarMeTransaction->amount;
            }

            \Log::debug("[capture]parameters:". print_r($pagarMeTransaction, 1));

            $pagarMeTransaction->capture(floor($amount));

            \Log::debug("[capture]response:". print_r($pagarMeTransaction, 1));

            return array(
                'success' => true,
                'status' => $pagarMeTransaction->status,
                'captured' => ($pagarMeTransaction->status == self::PAGARME_PAID),
                'paid' => ($pagarMeTransaction->status == self::PAGARME_PAID),
                'transaction_id' => strval($pagarMeTransaction->id)
            );
        } catch (PagarMe_Exception $ex) {
            \Log::error($ex->getMessage().$ex->getTraceAsString());

            return array(
                "success" 					=> false ,
                "type" 						=> 'api_capture_error' ,
                "code" 						=> $ex->getReturnCode() ,
                "message" 					=> trans("paymentError.".$ex->getReturnCode()) ,
                "transaction_id"			=> $transaction->gateway_transaction_id
            );
        }
    }

    public function refund(Transaction $transaction, Payment $payment)
    {
        if ($transaction && $transaction->status != Transaction::REFUNDED) {
            try {
                $refund = PagarMe_Transaction::findById($transaction->gateway_transaction_id);

                \Log::debug("[refund]parameters:". print_r($refund, 1));
                

                $refund->refund();

                \Log::debug("[refund]response:". print_r($refund, 1));

                return array(
                    "success" 			=> true ,
                    "status" 			=> $refund->status ,
                    "transaction_id" 	=> strval($refund->id),
                );
            } catch (Exception $ex) {
                \Log::error($ex->__toString().$ex->getTraceAsString());

				return array(
					"success" 			=> false ,
					"type" 				=> 'api_refund_error' ,
					"code" 				=> $ex->getCode(),
					"message" 			=> $ex->getMessage(),
					"transaction_id" 	=> $transaction->id ,
				);
		
		   }
		}
		else {
			$error = array(
				"success" 			=> false ,
				"type" 				=> 'api_refund_error' ,
				"code" 				=> 1 ,
				"message" 			=> trans("paymentError.noTrasactionRefundFound"),
				"transaction_id" 	=> null ,
			);
			
			\Log::error(print_r($error,1));

            return $error;
        }
    }

    public function refundWithSplit(Transaction $transaction, Payment $payment)
    {
        \Log::debug('refund with split');

        return($this->refund($transaction, $payment));
    }

    public function captureWithSplit(Transaction $transaction, Provider $provider, $totalAmount, $providerAmount, Payment $payment = null)
    {
        try {
            \Log::debug('capture with split');
            $requestId = $transaction->request_id;
            if ($provider && $provider->getBankAccount() && $provider->getBankAccount()->recipient_id && self::getRecipientId()) {
                $adminAmount = $totalAmount - $providerAmount;

                $pagarMeTransaction = PagarMe_Transaction::findById($transaction->gateway_transaction_id);

                if ($pagarMeTransaction == null) {
                    throw new PagarMe_Exception("Transaction not found.", 1);
                }

                //criar regra de split e capturar valores
                $param = array(
                    "amount" 		=> floor($totalAmount * 100),
                    "split_rules" 	=> array(
                        //prestador
                        array(
                            "recipient_id" 						=> $provider->getBankAccount()->recipient_id,
                            "amount" 							=> floor($providerAmount * 100),
                            "charge_processing_fee" 			=> $this->getReversedProcessingFeeCharge() ? true : false,
                            "liable" 							=> true  //assume risco de transação (possíveis estornos)
                        ),
                        //admin
                        array(
                            "recipient_id" 						=> self::getRecipientId(),
                            "amount" 							=> ceil($adminAmount * 100),
                            "charge_processing_fee" 			=> $this->getReversedProcessingFeeCharge() ? false : true, //responsável pela taxa de processamento
                            "liable" 							=> true  //assume risco da transação (possíveis estornos)
                        )
                    )
                );

                \Log::debug("[capture_split]parameters:". print_r($param, 1));
                $pagarMeTransaction->capture($param);

                return array(
                    'success' => true,
                    'status' => $pagarMeTransaction->status,
                    'captured' => ($pagarMeTransaction->status == self::PAGARME_PAID),
                    'paid' => ($pagarMeTransaction->status == self::PAGARME_PAID),
                    'transaction_id' => $pagarMeTransaction->id
                );
            } else {
                \Log::debug('provider without bank account and recipient_id, just do a normal capture');

                $chargeCapture =  $this->capture($transaction, $totalAmount, $payment);

                \Log::debug("[capture_split] chargeCapture response:". print_r($chargeCapture, 1));

                return $chargeCapture;
            }
        } catch (PagarMe_Exception $ex) {
            \Log::error($ex->getMessage().$ex->getTraceAsString());

            return array(
                "success" 					=> false ,
                "type" 						=> 'api_capture_error' ,
                "code" 						=> $ex->getReturnCode() ,
                "message" 					=> trans("paymentError.".$ex->getReturnCode()) ,
                "transaction_id"			=> $transaction->gateway_transaction_id
            );
        }
    }

    public function retrieve(Transaction $transaction, Payment $payment = null)
    {
        $pagarmeTransaction = PagarMe_Transaction::findById($transaction->gateway_transaction_id);

        return array(
            'success' => true,
            'transaction_id' => strval($pagarmeTransaction->id),
            'amount' => $pagarmeTransaction->amount,
            'destination' => '',
            'status' => $pagarmeTransaction->status,
            'card_last_digits' => $pagarmeTransaction->card_last_digits,
        );
    }

    public function getNextCompensationDate()
    {
        $carbon = Carbon::now();
        $compDays = Settings::findByKey('compensate_provider_days');
        $addDays = ($compDays || (string)$compDays == '0') ? (int)$compDays : 31;
        $carbon->addDays($addDays);
        
        return $carbon;
    }

    //retorna os recebíveis de uma transação
    private static function get_transaction_payables($transaction_id)
    {
        try {
            $url = sprintf(
                'https://api.pagar.me/1/transactions/%s/payables?api_key=%s',
                $transaction_id,
                Settings::findByKey('pagarme_api_key')
            );

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $data = curl_exec($ch);
            curl_close($ch);
            return json_decode($data);
        } catch (Exception $ex) {
            throw $ex;
        }
    }
        
    
    public function createOrUpdateAccount(LedgerBankAccount $ledgerBankAccount)
    {
        $return = [];
        $recipient = null;
        
        $bank 	 = Bank::where('id', $ledgerBankAccount->bank_id)->first();
        
        $settingTransferInterval 	= Settings::findByKey('provider_transfer_interval');
        $settingTransferDay 		= Settings::findByKey('provider_transfer_day');

        /**
         * Se recipient_id não iniciar com re_ (padrão do Pagar.me) não manda recupear as informações
         * do recipient_id e cria uma nova conta bancária.
         */
        if (($ledgerBankAccount->recipient_id) && strpos($ledgerBankAccount->recipient_id, "re_") === 0) {
            try {
                $recipient = PagarMe_Recipient::findById($ledgerBankAccount->recipient_id);
            } catch (PagarMe_Exception $ex) {
                \Log::error($ex->__toString().$ex->getTraceAsString());
            }
        } else {
            $ledgerBankAccount->recipient_id = null;
        }

        try {
            $bankAccount = array(
                "bank_code" 		=> $bank->code,
                "agencia" 			=> $ledgerBankAccount->agency,
                "agencia_dv" 		=> $ledgerBankAccount->agency_digit,
                "conta" 			=> $ledgerBankAccount->account,
                "type"				=> $ledgerBankAccount->account_type,
                "conta_dv" 			=> $ledgerBankAccount->account_digit,
                "document_number" 	=> $ledgerBankAccount->document,
                "legal_name" 		=> $ledgerBankAccount->holder
            );

            $transferDay = $settingTransferDay ? $settingTransferDay : "5";

            if (!$settingTransferInterval || $settingTransferInterval == "daily") {
                $transferDay = 0;
            }

            $recipientData = array(
                "transfer_interval" => $settingTransferInterval? $settingTransferInterval : "daily",
                "transfer_day" 		=> $transferDay,
                "transfer_enabled" 	=> true, //recebe pagamento automaticamente
                "bank_account" 		=> $bankAccount
            );

            if ($ledgerBankAccount->recipient_id) {
                $recipientData["id"] = $ledgerBankAccount->recipient_id ;
            }
            
            //\Log::info("[PagarMe_Recipient] Entrada: ". print_r($recipientData, 1));

            if (!$recipient) {
                $recipient = new PagarMe_Recipient($recipientData);
                $recipient->create();
            } elseif ($recipient && $recipient->id) {
                
                /**
                 * Para atualizar conta bancária é utilizado as funções SET presentes em PagarMe_Recipient.
                 */
                
                $recipient->setTransferIntervel($settingTransferInterval? $settingTransferInterval : "daily");
                $recipient->setTransferDay($transferDay);
                $recipient->setBankAccount($bankAccount);
                $recipient->save();
            }

            //\Log::info("[PagarMe_Recipient] Saida: ". print_r($recipientData, 1));

            
            if ($recipient->id == null) {
                $return['recipient_id'] = $recipient[0]->id;
            } else {
                $return['recipient_id'] = $recipient->id;
            }

            return array(
                "success" 					=> true ,
                "recipient_id" 				=> $return['recipient_id']
            );
        } catch (PagarMe_Exception  $ex) {
            \Log::error($ex->__toString().$ex->getTraceAsString());

            $return = array(
                "success" 					=> false ,
                "recipient_id"				=> null,
                "type" 						=> 'api_bankaccount_error' ,
                "code" 						=> $ex->getReturnCode() ,
                "message" 					=> trans("empty.".$ex->getMessage())
            );

            return $return;
        }
    }

    public function deleteCard(Payment $payment, User $user = null)
    {
        try {
            self::setApiKey();
            
            /*
            $card = PagarMe_Card::findById($payment->card_token);

            if($card)
                $card->delete();
            */
            return array(
                "success" 	=> true
            );
        } catch (Stripe\Error\Base $ex) {
            $body = $ex->getJsonBody();
            $error = $body['error'] ;
            
            if (array_key_exists('code', $body)) {
                $code = $body["code"];
            } else {
                $code = null ;
            }
    
            return array(
                "success" 	=> false ,
                'data' => null,
                'error' => array(
                    "code" 		=> ApiErrors::CARD_ERROR,
                    "messages" 	=> array(trans('creditCard.'.$error["code"]))
                )
            );
        }
    }

    public static function getRecipientId()
    {
        return Settings::findByKey('pagarme_recipient_id');
    }

    public function getGatewayTax()
    {
        return 0.0399;
    }

    public function getGatewayFee()
    {
        return 0.5 ;
    }

    private static function getReversedProcessingFeeCharge()
    {
        return true;
    }

    private function getBilling($paymentId)
    {
        $payment = Payment::find($paymentId);
        
        if (!$payment) {
            return false;
        }
        
        $user = $payment->user_id ? $payment->User : $payment->Provider;

        $zipcode = $user->getZipcode();
        $zipcode = $this->cleanWord($zipcode);
        
        return  array(
            "address" => array(
                "street" => $user->getStreet(),
                "street_number" => $user->getStreetNumber(),
                "neighborhood" => $user->getNeighborhood(),
                "city" => $user->address_city,
                "state" => $user->state,
                "zipcode" => $zipcode,
                "country" => "br"
            ),
            "name" => $user->getFullName()
        );
    }

    private function getItems($id, $description, $amount)
    {
        $item = array(
            "id" => (string) $id,
            "title" => $description,
            "unit_price" => $amount,
            "quantity" => 1,
            "tangible" => false
        );

        $items[] = $item;

        return $items;
    }
    public function checkAutoTransferProvider()
    {
        try {
            if (Settings::findByKey(self::AUTO_TRANSFER_PROVIDER) == "1") {
                return(true);
            } else {
                return(false);
            }
        } catch (Exception$ex) {
            \Log::error($ex);

            return(false);
        }
    }
    
    public static function checkCountry($country)
    {
        $count = strlen($country);
        if ($count > 2) {
            $response = substr($country, 0, 2);
        } else {
            $response = $country;
        }

        return $response;
    }

    public function cleanWord($word)
    {
        $word = str_replace(".", "", $word);
        $word = str_replace("-", "", $word);
        $word = str_replace("/", "", $word);
        $word = str_replace("/n", "", $word);

        return $word;
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
