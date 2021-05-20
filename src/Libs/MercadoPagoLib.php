<?php

use Carbon\Carbon;

/**
 * Class MercadoPagoLib
 *
 * @package MotoboyApp
 *
 * @author  Andre Gustavo <andre.gustavo@codificar.com.br>
 */
class MercadoPagoLib implements IPayment {

    //para buscar chaves no banco
    const MERCADOPAGO_PUBLIC_KEY = 'mercadopago_public_key';
    const MERCADOPAGO_ACCESS_TOKEN = 'mercadopago_access_token';

    //armaneza chaves
    public $public_key;
    public $access_token;

    //status
    const MP_APPROVED = 'approved';
    const MP_IN_PROCESS = 'in_process';
    const MP_REJECTED = 'rejected';
    const MP_AUTHORIZED = 'authorized';
    //

    const AUTO_TRANSFER_PROVIDER = 'auto_transfer_provider_payment';

    public function __construct() {
        $this->setApiKey();
        MercadoPago\SDK::setAccessToken($this->access_token);
    }

    /* Método para setar chaves pública e privada para requisições
     * @return string
     */

    private function setApiKey() {

        $this->public_key = Settings::findByKey(self::MERCADOPAGO_PUBLIC_KEY);
        $this->access_token = Settings::findByKey(self::MERCADOPAGO_ACCESS_TOKEN);
    }

    /* Método para criar cartão e associar a um comprador novo ou existente
     * @param payment Payment - instância do pagamento com dados do cartão
     * @param user User - instância do usuário para definir comprador  com dados do cartão
     * @return array
     */

    public function createCard(Payment $payment, $user = null) {

        try {

            //captura user do pagamento se não foi enviado
            if (!$user) {
                $user = $payment->User;
            }

            //recupera comprador no mercado pago
            $customer = $this->findCustomer($user);

            //cria comprador no mp caso não exista
            if ($customer == null)
                $customer_id = $this->createCustomer($user);
            else
                $customer_id = $customer->id;

            //cria cartão
            $card = new MercadoPago\Card();
            $card->customer_id = $customer_id;
            $card->token = $payment->card_token;
            $card->save();

            //se não ocorrer erros, salva no banco
            if ($card->error == null) {
                $payment->card_type = $card->payment_method->name;
                $payment->customer_id = $card->customer_id;
                $payment->card_token = $card->id;
                $payment->last_four = $card->last_four_digits;
                $payment->is_active = true;

                $payment->save();
            }

            //retorno
            return array(
                "success" => true,
                "token" => $payment->card_token,
                "card_token" => $card->id,
                "customer_id" => $payment->customer_id,
                "card_type" => strtolower($payment->card_type),
                "last_four" => $payment->last_four,
            );
        } catch (Exception $ex) {

            \Log::error($ex->getMessage());

            return array(
                "success" => false,
                "type" => $ex->getMessage(),
                "code" => $ex->getReturnCode(),
                "message" => trans("paymentError." . $ex->getReturnCode()),
            );
        }
    }

    /* Método para realizar cobrança no cartão do comprador sem repassar valor algum ao prestador
     * @param $payment Payment - instância do pagamento com dados do cartão
     * @param $amount Double - valor a ser transacionado
     * @param $description String - descrição da transação
     * @param $capture Boolean - definição de captura da transação
     * @return array
     */

    public function charge(Payment $payment, $amount, $description, $capture = true, $user = null) {

        try {

            //@#ag TEMPORARIO: para testes com navegador
            if ($payment->encrypted == null) {
                return View::make('mercadopago.cvv_pay')
                                ->with(array('card_id' => $payment->card_token,
                                    'last_four' => $payment->last_four,
                                    'card_type' => $payment->card_type,
                                    'action' => 'pay'));
            }

            //cria transação
            $paymp = new MercadoPago\Payment();
            $paymp->transaction_amount = $amount;
            $paymp->token = $payment->encrypted;
            $paymp->description = $description;
            $paymp->installments = 1;
            $paymp->payment_method_id = $payment->card_type;
            $paymp->payer = array(
                "type" => "customer",
                "id" => $payment->customer_id
            );

            //captura ou sem captura
            $paymp->capture = $capture;

            //salva
            $paymp->save();

            //retorno em caso de erro
            if ($paymp->status != self::MP_APPROVED && $paymp->status != self::MP_AUTHORIZED) {
                return array(
                    "success" => false,
                    "type" => 'api_charge_error',
                    "code" => 'api_charge_error',
                    "message" => $paymp->error,
                    "transaction_id" => $paymp->id
                );
            }

            //retorno sucesso
            return array(
                'success' => true,
                'captured' => $capture,
                'paid' => ($paymp->status == self::MP_APPROVED),
                'status' => $paymp->status,
                'transaction_id' => $paymp->id
            );
        } catch (Exception $ex) {

            \Log::error($ex->getMessage());

            return array(
                "success" => false,
                "type" => 'api_charge_error',
                "code" => $ex->getReturnCode(),
                "message" => trans("paymentError." . $ex->getReturnCode()),
                "transaction_id" => ''
            );
        }
    }

    /* Método para realizar cobrança no cartão do comprador com repasse ao prestador
     * @param $payment Payment - instância do pagamento com dados do cartão
     * @param $provider Provider - instância do prestador
     * @param $totalAmount Double - valor total a ser transacionado
     * @param $providerAmount Double - valor do prestador a ser transacionado
     * @param $description String - descrição da transação
     * @param $capture Boolean - definição de captura da transação
     * @return array
     */

    public function chargeWithSplit(Payment $payment, Provider $provider, $totalAmount, $providerAmount, $description, $capture = true, User $user = null) {

        try {

            //Recupera conta do prestador
            $bank_account = LedgerBankAccount::whereProviderId($provider->id)->first();

            //verifica existência
            if ($bank_account == null || $bank_account->recipient_id == null)
                throw new Exception("Conta do prestador nao encontrada.", 1);

            //configura ACCESS_TOKEN do provider
            MercadoPago\SDK::configure(['ACCESS_TOKEN' => $bank_account->recipient_id]);

            //@#ag TEMPORARIO: para testes com navegador
            if ($payment->encrypted == null) {
                return View::make('mercadopago.cvv_pay')
                                ->with(array('card_id' => $payment->card_token,
                                    'last_four' => $payment->last_four,
                                    'card_type' => $payment->card_type,
                                    'action' => 'paysplit'));
            }

            //cria transação
            $paymp = new MercadoPago\Payment();
            $paymp->transaction_amount = $totalAmount;
            $paymp->token = $payment->encrypted;
            $paymp->description = $description;
            $paymp->installments = 1;
            $paymp->payment_method_id = $payment->card_type;
            $paymp->payer = array(
                "type" => "customer",
                "id" => $payment->customer_id
            );

            //com captura ou sem captura
            $paymp->capture = $capture;

            //save
            $paymp->save();

            //retorno em caso de erro
            if ($paymp->status != self::MP_APPROVED) {
                return array(
                    "success" => false,
                    "type" => 'api_charge_error',
                    "code" => 'api_charge_error',
                    "message" => $paymp->error,
                    "transaction_id" => $paymp->id
                );
            }

            //retorno sucesso
            return array(
                'success' => true,
                'captured' => $capture,
                'paid' => ($paymp->status == self::MP_APPROVED),
                'status' => $paymp->status,
                'transaction_id' => $paymp->id
            );
        } catch (Exception $ex) {

            \Log::error($ex->getMessage());

            return array(
                "success" => false,
                "type" => 'api_charge_error',
                "code" => $ex->getReturnCode(),
                "message" => trans("paymentError." . $ex->getReturnCode()),
                "transaction_id" => ''
            );
        }
    }

    /* Método para capturar transação do comprador sem repassar valor algum ao prestador
     * @param $transaction Transaction - instância do transação
     * @param $amount Double - valor a ser transacionado
     * @param $payment Payment - instância do pagamento com dados do cartão
     * @return array
     */

    public function capture(Transaction $transaction, $amount, Payment $payment = null) {

        try {

            //Recupera transação
            $mpTransaction = $this->retrieve($transaction);

            //verifica existência
            if ($mpTransaction == null)
                throw new Exception("Transaction not found.", 1);

            //dd($mpTransaction);

            $return = MercadoPagoApi::pay($this->access_token, $mpTransaction['transaction_id']);

            //captura transação não funciona no MercadoPago\Payment
            //$paymp = MercadoPago\Payment::find_by_id($mpTransaction['transaction_id']);
            //$paymp->capture = "true";
            //$paymp->update();
            //dd($paymp);

            \Log::debug("[capture]parameters:" . print_r($return, 1));

            //retorno sucesso
            return array(
                'success' => true,
                'status' => $return['status'],
                'captured' => ($return['status'] == self::MP_APPROVED),
                'paid' => ($return['status'] == self::MP_APPROVED),
                'transaction_id' => $return['paymentId']
            );
        } catch (Exception $ex) {
            \Log::error($ex->getMessage());

            return array(
                "success" => false,
                "type" => 'api_capture_error',
                "message" => trans("paymentError." . $ex->getMessage()),
                "transaction_id" => $transaction->gateway_transaction_id
            );
        }
    }

    /* Método para realizar reembolso da transação
     * @param $transaction Transaction - instância da transação
     * @param $payment Payment - instância do pagamento
     * @return array
     */

    public function refund(Transaction $transaction, Payment $payment) {

        //verifica se a transação já foi reembolsada
        if ($transaction && $transaction->status != Transaction::REFUNDED) {

            try {
                //reembolsa transação
                $refund = new MercadoPago\Refund();
                $refund->payment_id = $transaction->gateway_transaction_id;
                $refund->save();

                \Log::debug("[refund]response:" . print_r($refund, 1));

                //retorno
                return array(
                    "success" => true,
                    "status" => $refund->status,
                    "transaction_id" => $refund->id,
                );
            } catch (Exception $ex) {

                \Log::error($ex->__toString());

                return array(
                    "success" => false,
                    "type" => 'api_refund_error',
                    "code" => $ex->getCode(),
                    "message" => $ex->getMessage(),
                    "transaction_id" => null,
                );
            }
        } else {
            $error = array(
                "success" => false,
                "type" => 'api_refund_error',
                "code" => 1,
                "message" => trans("paymentError.noTrasactionRefundFound"),
                "transaction_id" => null,
            );

            \Log::error(print_r($error, 1));

            return $error;
        }
    }

    /* Método para realizar reembolso da transação com split
     * @param $transaction Transaction - instância da transação
     * @param $payment Payment - instância do pagamento
     * @return array
     */

    public function refundWithSplit(Transaction $transaction, Payment $payment) {

        \Log::debug('refund with split');

        //chama reembolso padrão
        return($this->refund($transaction, $payment));
    }

    /* Método para realizar captura da transação do comprador com repasse ao prestador
     * @param $transaction Transaction - instância do transação
     * @param $provider Provider - instância do prestador
     * @param $totalAmount Double - valor total a ser transacionado
     * @param $providerAmount Double - valor do prestador a ser transacionado
     * @param $payment Payment - instância do pagamento com dados do cartão
     * @return array
     */

    public function captureWithSplit(Transaction $transaction, Provider $provider, $totalAmount, $providerAmount, Payment $payment = null) {

        try {

            //Recupera transação no MP
            $mpTransaction = $this->retrieve($transaction);

            //verifica existência
            if ($mpTransaction == null)
                throw new Exception("Transaction not found.", 1);

            //Recupera conta do prestador
            $bank_account = LedgerBankAccount::whereProviderId($provider->id)->first();

            //verifica existência
            if ($bank_account == null || $bank_account->recipient_id == null)
                throw new Exception("Conta do prestador nao encontrada.", 1);

            //configura ACCESS_TOKEN do provider
            //MercadoPago\SDK::configure(['ACCESS_TOKEN' => $bank_account->recipient_id]);

            $return = MercadoPagoApi::pay($bank_account->recipient_id, $mpTransaction['transaction_id']);

            //captura transação não funciona no MercadoPago\Payment
            //$paymp = MercadoPago\Payment::find_by_id($mpTransaction['transaction_id']);
            //$paymp->capture = "true";
            //$paymp->update();
            //dd($paymp);

            \Log::debug("[capture]parameters:" . print_r($return, 1));

            //retorno sucesso
            return array(
                'success' => true,
                'status' => $return['status'],
                'captured' => ($return['status'] == self::MP_APPROVED),
                'paid' => ($return['status'] == self::MP_APPROVED),
                'transaction_id' => $return['paymentId']
            );
        } catch (Exception $ex) {
            \Log::error($ex->getMessage());

            return array(
                "success" => false,
                "type" => 'api_capture_error',
                "message" => trans("paymentError." . $ex->getMessage()),
                "transaction_id" => $transaction->gateway_transaction_id
            );
        }
    }

    /* Método para recuperar transação
     * @param $transaction Transaction - instância da transação
     * @param $payment Payment - instância do pagamento
     * @return array
     */

    public function retrieve(Transaction $transaction, Payment $payment = null) {

        //recupera transação
        $mpTransaction = MercadoPago\Payment::find_by_id($transaction->gateway_transaction_id);

        //retorna
        return array(
            'success' => true,
            'transaction_id' => $mpTransaction->id,
            'amount' => $mpTransaction->transaction_amount,
            'destination' => '',
            'status' => $mpTransaction->status,
            'card_last_digits' => $mpTransaction->card->last_four_digits,
        );
    }

    public function createOrUpdateAccount(LedgerBankAccount $ledgerBankAccount) {
        //não utilizado para o mercado pago
    }

    /* Método para deletar cartão
     * @param $payment Payment - instância do pagamento com cartão
     * @return array
     */

    public function deleteCard(Payment $payment, User $user = null) {

        try {
            //recupera cartão
            $card = new MercadoPago\Card();
            $card = $card->read(array('customer_id' => $payment->customer_id, 'id' => $payment->card_token));

            //dd($card);
            //verifica existência
            if ($card == null)
                throw new Exception("Cartão não encontrado", 1);

            //deleta e retorna
            return MercadoPagoApi::deleteCard($this->access_token, $payment);

            //deleta
            //@#ag não está funcionando no MercadoPago\Card
            //$card->delete();
        } catch (Exception $ex) {

            return array(
                "success" => false,
                'data' => null,
                'error' => array(
                    "code" => ApiErrors::CARD_ERROR,
                    "messages" => $ex->getMessage()
                )
            );
        }
    }

    /* Método para criar o comprador
     * @param $user User - instância do usuário comprador
     * @return array
     */

    public function createCustomer($user) {

        //cria customer
        $customer = new MercadoPago\Customer();

        $customer->first_name = $user->first_name;
        $customer->last_name = $user->last_name;
        $customer->email = $user->email;
        $customer->phone = array('area_code' => "38", //@#ag colocar dinamico
            'number' => $user->phone
        );
        $customer->identification = array('type' => 'CPF',
            'number' => $user->document
        );
        $customer->description = "Customer for " . $user->email;
        $customer->address = array('zip_code' => $user->zipcode,
            'street_name' => $user->address,
            'street_number' => (int) $user->address_number
        );

        $customer->save();

        //retorna
        return $customer->id;
    }

    /* Método para procurar e retorna o comprador de forma específica
     * @param $user User - instância do usuário comprador
     * @return array
     */

    public function findCustomer($user) {

        try {

            //busca customer
            $customer = MercadoPago\Customer::search(array('email' => $user->email));

            //retorna customer
            return current($customer);
        } catch (Exception $ex) {

            \Log::error($ex->getMessage());

            return array(
                "success" => false,
                "type" => $ex->getMessage(),
                "code" => $ex->getReturnCode(),
                "message" => trans("paymentError." . $ex->getReturnCode()),
            );
        }
    }

    public function getNextCompensationDate(){
		$carbon = Carbon::now();
		$compDays = Settings::findByKey('compensate_provider_days');
		$addDays = ($compDays || (string)$compDays == '0') ? (int)$compDays : 31;
		$carbon->addDays($addDays);
		
		return $carbon;
	}

    public function getGatewayTax() {
        return 0.0399;
    }

    public function getGatewayFee() {
        return 0.5;
    }

    private static function getReversedProcessingFeeCharge() {
        return true;
    }

    public function checkAutoTransferProvider() {
        try {
            if (Settings::findByKey(self::AUTO_TRANSFER_PROVIDER) == "1")
                return(true);
            else
                return(false);
        } catch (Exception$ex) {
            \Log::error($ex);

            return(false);
        }
    }

}
