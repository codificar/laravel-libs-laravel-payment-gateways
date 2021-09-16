<?php

namespace Codificar\PaymentGateways\Models;

use Illuminate\Database\Eloquent\Relations\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Carbon\Carbon;
use Codificar\PaymentGateways\Libs\PaymentFactory;
use Eloquent;
use Payment, Settings;
use DB;
use Exception;

class GatewaysLibModel extends Eloquent
{

    protected $table = 'payment';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
	public $timestamps = true;
	
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

    public static function gatewayUpdateBankAccounts($ledgerBankAccounts)
    {
        if(
            Settings::findByKey('payment_card') == 1 || 
            Settings::findByKey('payment_debitCard') == 1 ||
            Settings::findByKey('payment_carto') == 1 ||
            Settings::findByKey('payment_crypt') == 1
        )
        {
            foreach ($ledgerBankAccounts as $ledgerBankAccount)
            {
                try
                {
                    $return = PaymentFactory::createGateway()->createOrUpdateAccount($ledgerBankAccount);
                    $ledgerBankAccount->recipient_id = $return['recipient_id'];
                    
                    $ledgerBankAccount->save();
                } catch (Exception $e) {
                    \Log::error(
                        "Change gateway update recip fail: " . print_r($ledgerBankAccount, 1) . 
                        " Error: " . $e->getMessage()
                    );
                    continue;
                }
            }
        }
    }
    
}
