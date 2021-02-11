<?php

/**
 * WARNING: THIS SEED IS AUTO-GENERATE IN "laravel-payment-gateways" LIB. IF YOU WANT TO MAKE ANY CHANGE IN THIS FILE, DO IT DIRECTLY IN THE LIBRARY
 * PERIGO: ESSA SEED E AUTO GERADA PELA BIBLIOTECA "laravel-payment-gateways". SE QUISER FAZER QUALQUER MUDANÇA NESSE ARQUIVO, FACA DIRETAMENTE NA BIBLIOTECA
 */

use Illuminate\Database\Seeder;

class GatewayUpdateCards extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //Get the new gateway name
        $newGatewayName = Settings::findByKey('default_payment');

        foreach (Payment::all() as $payment) {

            //If gateway is carto or the card is terracard, there is no need to update the cards
            if($payment->gateway == 'carto' || $payment->card_type == 'terracard') {
                $this->command->info(sprintf('Cartão %s nao modificado (carto - terracard)', $payment->id));
            } 
            else {
                try {                    
                    $cardNumber             = $payment->getCardNumber();
                    $cardExpirationMonth    = $payment->getCardExpirationMonth();
                    $cardExpirationYear     = $payment->getCardExpirationYear();
                    $cardCvv                = $payment->getCardCvc();
                    $cardHolder             = $payment->getCardHolder();

                    $return = [ 'success' => false];
                    
                    $gateway = PaymentFactory::createGateway();

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

                        //Show message in terminal
                        \Log::debug("Id do cartao: " . $payment->id . " - card token: " . $return['card_token']);
                        $this->command->info(sprintf('Cartão %s salvo no gateway %s com código %s', 
                                        $payment->id, 
                                        $newGatewayName,
                                        $return['card_token']
                                    ));
                    }
                    else {
                        $this->command->error(sprintf('Erro ao salvar cartão %s no gateway %s. Mensagem: %s.', 
                                        $payment->id, 
                                        $newGatewayName,
                                        $return['type']
                                    ));
                    }
                }
                catch (Exception $ex){
                    $this->command->error($ex->__toString());
                    continue ;
                }
            }
        }

        $this->command->info("Atualizacao de cartoes concluida!");
    }
}