<?php

namespace Codificar\PaymentGateways\Libs;
use Carbon\Carbon;

use Codificar\PaymentGateways\Libs\BancardApi;

use Exception;
//models do sistema
use Payment;
use Provider;
use Transaction;
use User;
use LedgerBankAccount;
use Settings;
use Requests;
use Illuminate\Support\Facades\App;

/**
 * Class BancardLib
 *
 * @package MotoboyApp
 *
 * @author  Andre Gustavo <andre.gustavo@codificar.com.br>
 */
class BancardLib implements IPayment
{

    //para buscar chaves no banco
    const BANCARD_PUBLIC_KEY = 'bancard_public_key';
    const BANCARD_PRIVATE_KEY = 'bancard_private_key';

    //armaneza chaves
    public $public_key;
    public $private_key;
    public $api;

    public static $APP_API_PROD = "https://vpos.infonet.com.py";
    public static $APP_API_DEV = "https://vpos.infonet.com.py:8888";

    //define split automático com provider
    const AUTO_TRANSFER_PROVIDER = 'auto_transfer_provider_payment';

    public function __construct()
    {
        $this->setApiKey();
    }

    /* Método para setar chaves pública e privada da bancard para requisições
     * @return string
     */

    private function setApiKey()
    {
        $this->public_key = Settings::findByKey(self::BANCARD_PUBLIC_KEY);
        $this->private_key = Settings::findByKey(self::BANCARD_PRIVATE_KEY);
        if (App::environment('production')) {
            $api = self::$APP_API_PROD;
        } else {
            $api = self::$APP_API_DEV;
        }
        $this->api = $api;
    }

    /* Método para solicitar a criação de um cartão na Bancard
     * @param $payment Payment - instância do pagamento com dados do cartão
     * @param $user User - para add cartão
     * @return array
     */

    public function createCard(Payment $payment, User $user = null)
    {

        try {
            //recupera user do payment caso não existe
            if (isset($payment->user)){
                $user = $payment->user;
            }
            if (isset($payment->provider)){
                $provider = $payment->provider;
            }
            //solicita criação de cartão e retorna um iframe para usuário preencher os dados
            $response = BancardApi::createCard($this->public_key, $this->private_key, $payment);
            
            //retorna iframe
            return $response;
        } catch (Exception $ex) {
            \Log::error($ex->getMessage());
            return array(
                "success" => false,
                "type" => 'api_create_card_error',
                "code" => 'api_create_card_error',
                "message" => trans("paymentError.api_create_card_error"),
            );
        }
    }

    /* Método para realizar cobrança no cartão do comprador sem repassar valor algum ao prestador
     * @param $payment Payment - instância do pagamento com dados do cartão
     * @param $amount Double - valor a ser transacionado
     * @param $description String - descrição da transação
     * @param $capture Boolean - definição de captura da transação (não utilizado na bancard)
     * @param $user User - usuário da transação
     * @return array
     */

    public function charge(Payment $payment, $amount, $description, $capture = true, User $user = null)
    {
        try {

            $user = User::where('id', $payment->user_id)->first();
            $provider = Provider::where('id', $payment->provider_id)->first();
            if($user){
                $lastRide = Requests::getUserLastRide($user->id);
                if($lastRide){
                    $description = sprintf($description, $lastRide->id);
                }
            }

            //busca cartões do user na bancard
            $cards = BancardApi::getCards($this->public_key, $this->private_key, $provider, $user);

            $aliasToken = null;
            //verifica se obteve sucesso na busca
            if (isset($cards->status) && $cards->status == BancardApi::STATUS_SUCCESS) {
                foreach ($cards->cards as $card) {

                    //verifica se o cartão buscado é o DEFAULT
                    if ($card->card_id == $payment->card_token) {
                        //busca alias_token que é atualizado frequentemente pela bancard
                        $aliasToken = $card->alias_token;
                    }
                }
            } else {
                return array(
                    'success' => false,
                    'message' => $cards['message'],
                    "type" => 'api_charge_error',
                    "code" => 'api_charge_error',
                    "transaction_id" => ''
                );
            }

            //verificar se existe um alias_token para o cartão default
            if ($aliasToken == null) {
                throw new Exception('card_not_found');
            }

            //formata valor a ser cobrado
            $amount_format = number_format(floatval($amount), 2, '.', '');
            //$amount_format = number_format(floatval("15003"), 2, '.', '') ;

            //solicita pagamento com alias_token do usuário
            $response = BancardApi::pay($this->public_key, $this->private_key, $amount_format, $description, $aliasToken);

            //verifica se deu erro
            if (!$response['success']) {
                \Log::error($response['message']);
                return array(
                    "success" => false,
                    "type" => 'api_charge_error',
                    "code" => $response['response_code'] ?? 'api_charge_error',
                    "message" => $response['message'] ?? 'paymentError.general',
                    "transaction_id" => ''
                );
            }

            //sucess
            return array(
                'success' => true,
                'captured' => $capture,
                'paid' => true,
                'status' => 'paid',
                'transaction_id' => $response['paymentId']
            );
        } catch (Exception $ex) {
            \Log::error($ex->getMessage());

            return array(
                "success" => false,
                "type" => 'api_charge_error',
                "code" => 'api_charge_error',
                "message" => trans("paymentError." . $ex->getMessage()),
                "transaction_id" => ''
            );
        }
    }

    public function chargeWithSplit(Payment $payment, Provider $provider, $totalAmount, $providerAmount, $description, $capture = true, User $user = null)
    {
        //não utilizado para bancard no momento
        return array(
            "success" => false,
            "type" => 'api_charge_error',
            "code" => 'api_charge_error',
            "message" => trans("paymentError.noAutoTransferProviderPayment"),
            "transaction_id" => ''
        );
    }

    public function capture(Transaction $transaction, $amount, Payment $payment = null)
    {
        //não utilizado para bancard no momento
        return array(
            "success" => false,
            "type" => 'api_charge_error',
            "code" => 'api_charge_error',
            "message" => trans("paymentError.noAutoTransferProviderPayment"),
            "transaction_id" => ''
        );
    }

    public function captureWithSplit(Transaction $transaction, Provider $provider, $totalAmount, $providerAmount, Payment $payment = null)
    {
        //não utilizado para bancard no momento
        return array(
            "success" => false,
            "type" => 'api_charge_error',
            "code" => 'api_charge_error',
            "message" => trans("paymentError.noAutoTransferProviderPayment"),
            "transaction_id" => ''
        );
    }

    /* Método para realizar reembolso da transação
     * @param $transaction Transaction - instância da transação
     * @param $payment Payment - instância do pagamento
     * @return array
     */

    public function refund(Transaction $transaction, Payment $payment)
    {

        try {

            //solicita o estorno do pagamento
            $response = BancardApi::payback($this->public_key, $this->private_key, $transaction->gateway_transaction_id);

            //verifica se deu erro
            if (!$response['success']) {
                \Log::error($response['message']);

                return array(
                    "success" => false,
                    "type" => 'api_refund_error',
                    "code" => 'api_refund_error',
                    "message" => $response['message']
                );
            }

            //success
            return array(
                "success" => true,
                "status" => 'refunded',
                "transaction_id" => $transaction->gateway_transaction_id,
            );
        } catch (Exception $ex) {

            \Log::error($ex->__toString());

            return array(
                "success" => false,
                "type" => 'api_refund_error',
                "code" => 'api_refund_error',
                "message" => $ex->getMessage(),
                "transaction_id" => $transaction->gateway_transaction_id
            );
        }
    }

    public function refundWithSplit(Transaction $transaction, Payment $payment)
    {
        //não utilizado para bancard no momento
    }

    /* Método para recuperar transação na Bancard
     * @param $transaction Transaction - instância da transação
     * @param $payment Payment - instância do pagamento
     * @return array
     */

    public function retrieve(Transaction $transaction, Payment $payment = null)
    {

        try {

            //solicita a recuperação do pagamento
            $response = BancardApi::retrieve($this->public_key, $this->private_key, $transaction->gateway_transaction_id);

            $amountFormatted = $response['amount_cents'] * 100;
            //verifica se deu erro ou rollback
            if (!$response['success']) {

                foreach ($response['message'] as $erro) {
                    if ($erro->key == BancardApi::STATUS_REFUND) {
                        //refund
                        return array(
                            'success' => true,
                            'transaction_id' => $transaction->gateway_transaction_id,
                            'amount' => $amountFormatted,
                            'destination' => '',
                            'status' => 'refund',
                            'card_last_digits' => $payment->last_four,
                        );
                    }
                }
                //error
                return array(
                    "success" => false,
                    "type" => 'api_retrieve_error',
                    "code" => 'api_retrieve_error',
                    "message" => $response['message']
                );
            }

            //sucess
            return array(
                'success' => true,
                'transaction_id' => $response['id'],
                'amount' => $amountFormatted,
                'destination' => '',
                'status' => 'paid',
                'card_last_digits' => $payment->last_four,
            );
        } catch (Exception $ex) {

            \Log::error($ex->__toString());

            return array(
                "success" => false,
                "type" => 'api_refund_error',
                "code" => 'api_refund_error',
                "message" => $ex->getMessage(),
                "transaction_id" => $transaction->gateway_transaction_id
            );
        }
    }

    /* Método para deletar cartão na Bancard
     * @param $payment Payment - instância do pagamento com cartão
     * @param $user User - usuário do cartão
     * @return array
     */

    public function deleteCard(Payment $payment, User $user = null)
    {

        try {

            //recupera user
            $user = User::where('id', $payment->user_id)->first();
            $provider = Provider::where('id', $payment->provider_id)->first();

            //busca cartões do user na bancard
            $cards = BancardApi::getCards($this->public_key, $this->private_key, $provider, $user);


            $aliasToken = null;
            //verifica se obteve sucesso na busca
            if ($cards->status == BancardApi::STATUS_SUCCESS) {
                foreach ($cards->cards as $card) {

                    //verifica se o cartão buscado é o DEFAULT
                    if ($card->card_id == $payment->card_token) {
                        //busca alias_token que é atualizado frequentemente pela bancard
                        $aliasToken = $card->alias_token;
                    }
                }
            }

            //verificar se existe um alias_token para o cartão default
            if ($aliasToken) {
                //solicita a remoção do cartão
                $response = BancardApi::deleteCard($this->public_key, $this->private_key, $payment, $aliasToken);

                //verifica se deu erro
                if (!$response['success']) {
                    \Log::error($response['message']);

                    return array(
                        "success" => false,
                        "error" => array(
                            "code" => 'api_deleteCard_error',
                            "messages" => $response['message']
                        )
                    );
                }
            }

            //sucess
            return array(
                "success" => true,
                'card_id' => $payment->id
            );
        } catch (Exception $ex) {

            \Log::error($ex->getMessage());

            return array(
                "success" => false,
                "error" => array(
                    "code" => 'api_deleteCard_error',
                    "messages" => $ex->getMessage()
                )
            );
        }
    }

    public function createOrUpdateAccount(LedgerBankAccount $ledgerBankAccount)
    {
        //não utilizado para bancard no momento
    }

    public function getGatewayFee()
    {
        return 0.5;
    }

    public function getGatewayTax()
    {
        return 0.0399;
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
        try {
            if (Settings::findByKey(self::AUTO_TRANSFER_PROVIDER) == "1")
                return (true);
            else
                return (false);
        } catch (Exception $ex) {
            \Log::error($ex);

            return (false);
        }
    }

    public function billetCharge($amount, $client, $postbackUrl, $billetExpirationDate, $billetInstructions)
	{
		\Log::error('billet_charge_not_implemented_in_Bancard_gateway');

		return array (
			'success' => false,
			'captured' => false,
			'paid' => false,
			'status' => false,
			'transaction_id' => null,
			'billet_url' => '',
			'billet_expiration_date' => ''
		);
	}

	public function billetVerify($request, $transaction_id = null)
	{
		\Log::error('billet_charge_not_implemented_in_stripe_gateway');

		return array (
			'success' => false,
			'captured' => false,
			'paid' => false,
			'status' => false,
			'transaction_id' => null,
			'billet_url' => '',
			'billet_expiration_date' => ''
		);
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
