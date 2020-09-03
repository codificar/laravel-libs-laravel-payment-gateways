<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Transbank\Webpay\Configuration;
use Transbank\Webpay\Oneclick;
use Transbank\Webpay\Oneclick\MallInscription;
use Transbank\Webpay\Oneclick\MallTransaction;
use Transbank\Webpay\Webpay;

/**
 * Class TransbankLib
 *
 * @package UberFretes
 *
 * @author  Andre Gustavo <andre.gustavo@codificar.com.br>
 */
class TransbankLib implements IPayment
{
    //armaneza commerce_code
    public $webpay;
    public $commerce_code;
    public $public_cert;
    public $private_key;

    //define split automático com provider
    const AUTO_TRANSFER_PROVIDER = 'auto_transfer_provider_payment';

    public function __construct()
    {
        $this->commerce_code = Settings::getTransbankCommerceCode();
        $this->public_cert = Settings::getTransbankPublicCert();
        $this->private_key = Settings::getTransbankPrivateKey();

        //se não estiver em ambiente de produção, retorna o dev
        if (App::environment('production')) {
            //seta conf para produção
            $this->configureOneclickMallForProduction();
        } else {
            //seta conf para testes
            $this->configureOneclickMallForTesting();
        }
    }

    /* Método para setar chaves pública e privada da Transbank para requisições
     * @return string
     */
    public function configureOneclickMallForProduction()
    {
        $configuration = new Configuration();
        $configuration->setEnvironment('PRODUCTION');
        $configuration->setCommerceCode($this->commerce_code);
        $configuration->setPublicCert($this->public_cert);
        $configuration->setPrivateKey($this->private_key);

        $this->webpay = new Webpay($configuration);
    }

    /* Método para setar chaves pública e privada da Transbank para requisições
     * @return string
     */
    public function configureOneclickMallForTesting()
    {
        $this->commerce_code = Settings::getTransbankCommerceCode();
        Oneclick::configureOneclickMallForTesting();
    }

    /* Método para converter url para ser passada via parametro
     * @return string
     */
    public function urlConvert($url_webpay, $revert = false)
    {
        //@ag (temporário?) - encontrar maneira melhor de passar
        if (!$revert) {
            $url_webpay = str_replace(':', '@', $url_webpay);
            $url_webpay = str_replace('/', '!', $url_webpay);
            $url_webpay = str_replace('.', '$', $url_webpay);
        } else {
            $url_webpay = str_replace('@', ':', $url_webpay);
            $url_webpay = str_replace('!', '/', $url_webpay);
            $url_webpay = str_replace('$', '.', $url_webpay);
        }

        return $url_webpay;
    }

    /* Método para solicitar a criação de um cartão na Transbank
     * @param $payment Payment - instância do pagamento com dados do cartão
     * @param $user User - para add cartão
     * @param $provider Provider - para add cartão
     * @return array
     */

    public function createCard(Payment $payment, $user = null, $provider = null)
    {
        try {

            //recupera user do payment caso não existe
            if (!$user)
                $user = $payment->User;

            //identificador del usuario en el comercio
            $username = $user->first_name;

            //correo electrónico del usuario
            $email = $user->email;

            //url de retorno
            $response_url = \Config::get('app.url') . "/transbank/card_return";

            //realiza cadastro
            $response = MallInscription::start($username, $email, $response_url);

            //recupera token e url pra post
            $tbk_token = $response->getToken();
            $url_webpay = $this->urlConvert($response->getUrlWebpay());

            //recupera cartão auxiliar existente para gerar novo
            $payment = Payment::whereUserId($user->id)->whereCardType('brand')->whereLastFour('0000')->first();

            //se não houver, cria
            if (!$payment)
                $payment = new Payment();

            //dados de cartão neutro para gerar novo
            $payment->card_type = "brand";
            $payment->ledger_id = $user->ledger->id;
            $payment->user_id = $user->id;
            $payment->customer_id = $tbk_token;
            $payment->last_four = "0000";
            $payment->is_default = false;
            $payment->is_active = false;
            $payment->encrypted = $tbk_token;
            $payment->save();

            return \Config::get('app.url') . "/transbank/card/" . $tbk_token . "/" . $url_webpay;
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
     * @param $capture Boolean - definição de captura da transação (não utilizado na Transbank)
     * @param $user User - usuário da transação
     * @return array
     */

    public function charge(Payment $payment, $amount, $description, $capture = true, $user = null)
    {

        try {

            //recupera user do payment caso não existe
            if (!$user)
                $user = $payment->User;

            // Identificador del usuario en el comercio
            $username = $user->first_name;
            $tbkUser = $payment->customer_id;
            $parentBuyOrder = rand(100000, 999999999);

            //detalhes da transação
            $details = [
                [
                    "commerce_code" => $this->commerce_code,
                    "buy_order" => $parentBuyOrder,
                    "amount" => number_format($amount, 0, '.', ''),
                    "installments_number" => 1
                ]
            ];

            //realiza pagamento
            $response = MallTransaction::authorize($username, $tbkUser, $parentBuyOrder, $details);

            //verifica se deu erro
            if ($response->details[0]["status"] != "AUTHORIZED") {

                return array(
                    "success" => false,
                    "type" => 'api_charge_error',
                    "code" => 'api_charge_error',
                    "message" => trans("paymentError.general"),
                    "transaction_id" => ''
                );
            }

            //sucess
            return array(
                'success' => true,
                'captured' => $capture,
                'paid' => true,
                'status' => 'paid',
                'transaction_id' => $response->buyOrder
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
        //não utilizado para transbank no momento
        return array(
            "success" => false,
            "type" => 'api_charge_error',
            "code" => 'api_charge_error',
            "message" => trans("paymentError.noAutoTransferProviderPayment"),
            "transaction_id" => ''
        );
    }

    public function capture(Transaction $transaction, $amount, Payment $payment)
    {
        //não utilizado para transbank no momento
        return array(
            "success" => false,
            "type" => 'api_charge_error',
            "code" => 'api_charge_error',
            "message" => trans("paymentError.noAutoTransferProviderPayment"),
            "transaction_id" => ''
        );
    }

    public function captureWithSplit(Transaction $transaction, Provider $provider, $totalAmount, $providerAmount, Payment $payment)
    {
        //não utilizado para transbank no momento
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

            //detalhes da transação
            $buyOrder = $transaction->gateway_transaction_id;
            $childCommerceCode = $this->commerce_code;
            $childBuyOrder = $transaction->gateway_transaction_id;
            $amount = $transaction->gross_value;

            //realiza cancelamento
            $response = MallTransaction::refund($buyOrder, $childCommerceCode, $childBuyOrder, $amount);

            //verifica se deu erro
            if ($response->type != "REVERSED") {
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
        //não utilizado para transbank no momento
    }

    /* Método para recuperar transação na Transbank
     * @param $transaction Transaction - instância da transação
     * @param $payment Payment - instância do pagamento
     * @return array
     */

    public function retrieve(Transaction $transaction, Payment $payment)
    {
        try {

            //recupera transação
            $response = MallTransaction::getStatus($transaction->gateway_transaction_id);

            //verifica se deu erro ou rollback
            if (count($response->details)) {
                //refund
                return array(
                    'success' => true,
                    'transaction_id' => $response->details[0]['buy_order'],
                    'amount' => $response->details[0]['amount'],
                    'destination' => '',
                    'status' => 'refund',
                    'card_last_digits' => $response->cardNumber,
                );
            } else {
                //error
                return array(
                    "success" => false,
                    "type" => 'api_retrieve_error',
                    "code" => 'api_retrieve_error',
                    "message" => $response['message']
                );
            }
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

    /* Método para deletar cartão na Transbank
     * @param $payment Payment - instância do pagamento com cartão
     * @param $user User - usuário do cartão
     * @return array
     */

    public function deleteCard(Payment $payment, User $user = null)
    {
        try {

            //recupera user
            if (!$user)
                $user = $payment->User;

            //remove cartão
            $response = MallInscription::delete($payment->customer_id, $user->first_name);

            //verifica se deu erro
            if ($response->status != 'OK') {

                return array(
                    "success" => false,
                    "error" => array(
                        "code" => 'api_deleteCard_error',
                        "messages" => $response->code
                    )
                );
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
        //não utilizado para transbank no momento
    }

    public function getGatewayFee()
    {
        return 0.5;
    }

    public function getGatewayTax()
    {
        return 0.0399;
    }

    public function getNextCompensationDate()
    {
        $carbon = Carbon::now();
        $carbon->addDays(31);
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
}
