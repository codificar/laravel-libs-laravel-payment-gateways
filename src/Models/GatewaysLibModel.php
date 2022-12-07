<?php

namespace Codificar\PaymentGateways\Models;

use Illuminate\Database\Eloquent\Relations\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Carbon\Carbon;
use Codificar\PaymentGateways\Libs\PaymentFactory;
use Eloquent;
use Payment, Settings, LedgerBankAccount;
use DB;
use Exception;

class GatewaysLibModel extends Eloquent
{
    

    const keysPaymentMethods =  array(
        'payment_money',
        'payment_card',
        'payment_machine',
        'payment_carto',
        'payment_crypt',
        'payment_debitCard',
        'payment_balance',
        'payment_prepaid',
        'payment_billing',
        'payment_direct_pix',
        'payment_gateway_pix'
    );

    //Custom names of payment methods
    const nameKeysPaymentMethods =  array(
        'name_payment_money',
        'name_payment_card',
        'name_payment_machine',
        'name_payment_carto',
        'name_payment_crypt',
        'name_payment_debitCard',
        'name_payment_balance',
        'name_payment_prepaid',
        'name_payment_billing',
        'name_payment_direct_pix',
        'name_payment_gateway_pix'
    );

    // Values used to save at request table
    const CARD = 0;
	const MONEY = 1;
	const CARTO = 2;
	const MACHINE = 3;
	const CRYPT = 4;
	const CARD_DEBIT = 6;
	const ASSOCIATION = 5;
	
	const BALANCE = 7;
	const BILLING = 8;

	const GATEWAY_PIX = 9;
	const DIRECT_PIX = 10;

    const valuesPayment =  [
        'payment_money'        => self::MONEY,
        'payment_card'         => self::CARD,
        'payment_machine'      => self::MACHINE,
        'payment_carto'        => self::CARTO,
        'payment_crypt'        => self::CRYPT,
        'payment_debitCard'    => self::CARD_DEBIT,
        'payment_balance'      => self::BALANCE,
        'payment_prepaid'      => self::BALANCE,
        'payment_billing'      => self::BILLING,
        'payment_direct_pix'   => self::GATEWAY_PIX,
        'payment_gateway_pix'  => self::DIRECT_PIX,
   ];

    protected $table = 'payment';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
	public $timestamps = true;


    /**
     * get array of payments available
     *
     * @return array
     */
    public static function getPaymentsAvailable($chatBot = false)
	 {
		$paymentArray = [];

		// card
		if ($payment = self::getPayment('payment_card')) {
			$payment['index'] = self::CARD;
			$paymentArray[] = $payment;
		}

		// money
		if ($payment = self::getPayment('payment_money')) {
			$payment['index'] = self::MONEY;
			$paymentArray[] = $payment;
		}

		// carto
		if ($payment = self::getPayment('payment_carto')) {
			$payment['index'] = self::CARTO;
			$paymentArray[] = $payment;
		}

		// machine
		if ($payment = self::getPayment('payment_machine')) {
			$payment['index'] = self::MACHINE;
			$paymentArray[] = $payment;
		}

		// crypt
		if ($payment = self::getPayment('payment_crypt')) {
			$payment['index'] = self::CRYPT;
			$paymentArray[] = $payment;
		}

		// debit card
		if ($payment = self::getPayment('payment_debitCard')) {
			$payment['index'] = self::CARD_DEBIT;
			$paymentArray[] = $payment;
		}

		// Balance
		if ($payment = self::getPayment('payment_balance')) {
			$payment['index'] = self::BALANCE;
			$paymentArray[] = $payment;
		}

		// Billing
		if ($payment = self::getPayment('payment_billing')) {
			$payment['index'] = self::BILLING;
			$paymentArray[] = $payment;
		}

		if ($chatBot)
			return array_filter(
				$paymentArray, 
				function ($query) { 
					if ($query['active'] && in_array($query['index'], \RequestCharging::CHATBOT_WHITE_LIST))
						return true; 
				}
			);
		// Gateway Pix
		if ($payment = self::getPayment('payment_gateway_pix')) {
			$payment['index'] = self::GATEWAY_PIX;
			$paymentArray[] = $payment;
		}

		// Direct Pix
		if ($payment = self::getPayment('payment_direct_pix')) {
			$payment['index'] = self::DIRECT_PIX;
			$paymentArray[] = $payment;
		}

		return array_filter(
			$paymentArray,
			function ($query) {
				if ($query['active']) return true;
			}
		);
	}

    /**
     * get payment description array
     *
     * @return array
     */
    public static function getPayment($key)
	{

		$setting = \Settings::where('key', $key)->first();

		if ($setting) {
			return [
				"id" 		    => $setting->id,
				"name" 		    => trans('payment.' . $key),
				"custom_name" 	=> \Settings::findByKey('name_'.$key),
				"active" 	    => (bool) $setting->value,
				"key"		    => $key
			] ;
 		}
 		else return null ;
    }
	
	public static function getUpdateCardsEstimateTime(){
		//Get total cards except 'carto'
		$totalCards = Payment::where('gateway', '!=', 'carto')->count();
		$totalCards = $totalCards ? $totalCards : 0;

        $estimateTimeSec = $totalCards * 2; // estimate two seconds for each card
        if($estimateTimeSec >= 60) {
            $estimateMsg = round($estimateTimeSec/60) . " minuto(s)";
        } else {
            $estimateMsg = $estimateTimeSec . " segundos";
		}
		return $estimateMsg;
	}

	public static function gatewayUpdateCards(){
		//Get the new gateway name
		$newGatewayName = Settings::findByKey('default_payment');

		\Log::alert("Atualizando cartoes no gateway " . $newGatewayName . ". No final sera gerado 2 logs: um contendo os cartoes atualizados com sucesso e outro com cartoes recusados pelo novo gateway. Estimativa de " . GatewaysLibModel::getUpdateCardsEstimateTime());

		$errCards = array();
		$sucCards = array();
		
        foreach (Payment::all() as $payment) {

            //If gateway is carto or the card is terracard, there is no need to update the cards
            if($payment->gateway == 'carto' || $payment->card_type == 'terracard') {
				array_push($sucCards, sprintf('Cartão %s nao modificado (carto - terracard)', $payment->id));
            } 
            else {
                try {                    
                    $cardNumber             = $payment->getCardNumber();
                    $cardExpirationMonth    = $payment->getCardExpirationMonth();
                    $cardExpirationYear     = $payment->getCardExpirationYear();
                    $cardCvv                = $payment->getCardCvc();
                    $cardHolder             = $payment->getCardHolder();

                    $return = [ 'success' => false];
                    
                    $gateway = \PaymentFactory::createGateway();

                    $return = $gateway->createCard($payment, $payment->User);

                    if($return['success']){
                        //if gateway has card_token, save the new value in database
                        if(isset($return['card_token'])) 
                            $payment->card_token = $return['card_token'];

                        //if gateway has customer_id, save the new value in database
                        if(isset($return['customer_id'])) 
                            $payment->customer_id = $return['customer_id'];
                        
                        //save the gateway name in database
                        $payment->gateway = $newGatewayName;

                        //update the card in database
                        $payment->save();
						array_push($sucCards, sprintf('Cartão %s salvo', $payment->id));
                    }
                    else {
						$errMsg = "Erro desconhecido";
						if($return && isset($return['message']) && $return['message'] && is_string($return['message'])) {
							$errMsg = $return['message'];
						}
                        array_push($errCards, sprintf('Erro ao salvar o cartão %s: %s', $payment->id, $errMsg));
                    }
                }
                catch (\Throwable $ex) {
					array_push($errCards, sprintf('Erro ao salvar o cartão %s: Erro desconhecido', $payment->id));
                    continue ;
                }
            }
        }
		\Log::alert("Atualizacao de cartoes concluida!");
		\Log::alert(print_r($errCards, true));
		\Log::alert(print_r($sucCards, true));
	}

    public static function gatewayUpdateBankAccounts(){

        foreach (LedgerBankAccount::all() as $ledgerBankAccount)
        {
            try
            {
                if(
                    Settings::findByKey('payment_card') == 1 || 
                    Settings::findByKey('payment_debitCard') == 1 ||
                    Settings::findByKey('payment_carto') == 1 ||
                    Settings::findByKey('payment_crypt') == 1
                )
                {
                    $return = PaymentFactory::createGateway()->createOrUpdateAccount($ledgerBankAccount);
                    $ledgerBankAccount->recipient_id = $return['recipient_id'];
                }
                
                $ledgerBankAccount->save();
            } catch (Exception $e) {
                \Log::error("Change gateway update recip: ".print_r($e->getMessage(),1));
                continue;
            }
        }
    }

    public static function UpdateBankAccounts(Array $ledgerBankAccounts){

        foreach ($ledgerBankAccounts as $ledgerBankAccount)
        {
            $ledgerBankAccount = \LedgerBankAccount::where(['id' => $ledgerBankAccount['id']])
                ->first();
            
            if($ledgerBankAccount) {
                echo print_r("\n - ID: [ " . $ledgerBankAccount->id . " ]");
                echo print_r("\n - Name: [" . $ledgerBankAccount->holder . "]");

                try {
                    $response = \LedgerBankAccount::createOrUpdateByGateway(
                        $ledgerBankAccount->provider_id,
                        $ledgerBankAccount->holder,
                        $ledgerBankAccount->document,
                        $ledgerBankAccount->bank_id,
                        $ledgerBankAccount->agency,
                        $ledgerBankAccount->agency_digit,
                        $ledgerBankAccount->account,
                        $ledgerBankAccount->account_digit,
                        $ledgerBankAccount->account_type,
                        $ledgerBankAccount->person_type,
                        $ledgerBankAccount->birthday_date,
                        $ledgerBankAccount->provider_document_id
                    );

                    if($response && isset($response['success'])) {
                        $ledgerBankAccount->recipient_id = $response['recipient_id'];
                        $ledgerBankAccount->gateway = $response['gateway'];
                        $ledgerBankAccount->save();

                        $isSave = filter_var($response['success'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) 
                            ? 'Salvo/Atualizado' 
                            : 'Não Salvo/Atualizado'; 
                        echo print_r("\n # status: " . $isSave . "\n\n");
                    } else {
                        echo print_r("\n # Não atualizado \n\n" );
                    }

                } catch (Exception $e) {
                    \Log::error($e->getMessage() . $e->getTraceAsString());
                    continue;
                }

            }
        }
    }
    
}
