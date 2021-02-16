<?php

namespace Codificar\PaymentGateways\Libs;

use Carbon\Carbon;
use Zoop\ZoopBase;
use Zoop\Lib\APIResource;
use Zoop\Facades\ZoopSellers;
use Zoop\Facades\ZoopBuyers;
use Zoop\Facades\ZoopCards;
use Zoop\Facades\ZoopChargesCNP;
use Zoop\Facades\ZoopTokens;
use Zoop\Facades\ZoopSplitTransactions;
use Zoop\src\Exceptions\ZoopAuthenticationException;
use Zoop\Exceptions\ZoopObjectNotFound;

//models do sistema
use Payment;
use Provider;
use Transaction;
use User;
use LedgerBankAccount;
use Settings;

class ZoopLib implements IPayment
{

    //Uma nova transação foi criada e as informações foram recebidas com sucesso para processamento.
    const ZOOP_NEW = 'new';
    //A transação foi encaminhada para processamento;
    const ZOOP_PENDING = 'pending';
    //Quando as Transações são emitidas com o parâmetro de captura como falso, uma pré-autorização é criada e precisará ser capturada mais tarde.
    const ZOOP_PRE_AUTHORIZED = 'pre_authorized'; //Quando as Transações são emitidas com o parâmetro de captura como falso, uma pré-autorização é criada e precisará ser capturada mais tarde.
    //O pagamento foi autorizado e valor estará disponível para recebimento pelo vendedor.
    const ZOOP_SUCCEEDED = 'succeeded';
    //A solicitação de autorização falhou ou foi negada pelo emissor do cartão.
    const ZOOP_FAILED = 'failed';
    //Uma ocorre quando existe um problema na comunicação entre a solução de captura, no caso de venda CP, ou entre a plataforma da zoop e o autorizador, podendo ocorrer antes que a transação tenha sido confirmada, não acarretando em débito para o comprador, ou posterior a aprovação da compra junto ao emissor. Neste último caso o sistema de captura da zoop registra a falha e desfaz automaticamente a venda através de uma operação de estorno com retorno do crédito na fatura do comprador.
    const ZOOP_REVERSED = 'reversed';
    //A diferença entre um vazio e um reembolso se resume a se uma transação foi ou não resolvida. Uma transação instável pode ser anulada. Uma vez que uma transação foi resolvida, ela só pode ser reembolsada.
    const ZOOP_REFUNDED = 'refunded';
    //O pagamento foi cancelado / anulado pelo vendedor ou pelo aplicativo. As taxas cobradas originalmente também são anuladas.
    const ZOOP_CANCELED = 'canceled';
    //Quando o titular do cartão envia uma queixa comunicando-se com o banco emissor sobre a transação errada, o banco emissor abre uma disputa com o comerciante, solicitando a documentação necessária e a prova para remediar o estorno. Se a documentação fornecida for satisfatória, a disputa é simplesmente recusada e o cliente é cobrado com uma taxa de processamento. Se os documentos parecem ser insatisfatórios, o montante do reembolso será fornecido ao cliente e o comerciante será debitado.
    const ZOOP_DISPUTE = 'dispute';
    //O status indica que a cobrança foi contestada pelo comprador e o vendedor perdeu a disputa, sendo o valor total da venda debitado da conta do vendedor.
    const ZOOP_CHARGE_BACK = 'charged_back';
    // Transação esperando pagamento
    const ZOOP_WAITING_PAYMENT = 'waiting_payment';

    const ZOOP_BILLET_CHARGE = 'billet_charge';
    
    const AUTO_TRANSFER_PROVIDER = 'auto_transfer_provider_payment';

    public function __construct()
    {
    }

    /* Método para criar cartão na ZOOP e associar a um comprador novo ou existente
     * @param payment Payment - instância do pagamento com dados do cartão
     * @param user User - instância do usuário para definir comprador  com dados do cartão
     * @return array
     */

    public function createCard(Payment $payment, User $user = null)
    {

        try {

            //recupera informações do cartão
            $cardNumber = $payment->getCardNumber();
            $cardExpirationMonth = $payment->getCardExpirationMonth();
            $cardExpirationYear = $payment->getCardExpirationYear();
            $cardCvv = $payment->getCardCvc();
            $cardHolder = $payment->getCardHolder();
            $cardExpirationYear = $cardExpirationYear % 100;
            $last4 = substr($cardNumber, -4);

            //captura user do pagamento se não foi enviado
            if (!$user) {
                $user = $payment->User;
            }

            //recupera comprador na zoop
            $customer = $this->findCustomerAll($user);

            //cria comprador na zoop caso não exista
            if ($customer == null)
                $customer_id = $this->createCustomer($user);
            else
                $customer_id = $customer->id;

            //cria o cartão na ZOOP
            $token = ZoopTokens::tokenizeCard([
                'holder_name' => $cardHolder,
                'expiration_month' => str_pad($cardExpirationMonth, 2, '0', STR_PAD_LEFT),
                'expiration_year' => str_pad($cardExpirationYear, 2, '0', STR_PAD_LEFT),
                'security_code' => $cardCvv,
                'card_number' => $cardNumber,
            ]);

            //associa o cartão criado ao comprador
            $associate = $this->associateWithACustomer($token->id, $customer_id);

            return array(
                "success" => true,
                "token" => $token->id,
                "card_token" => $token->card->id,
                "customer_id" => $associate->customer,
                "card_type" => strtolower($token->card->card_brand),
                "last_four" => $last4,
                "gateway"   => "zoop"
            );
        } catch (Zoop\Exceptions\ZoopException $ex) {

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

    public function charge(Payment $payment, $amount, $description, $capture = true, User $user = null)
    {

        try {

            //recupera cartão na zoop
            $card = ZoopCards::get($payment->card_token);

            if ($card == null)
                throw new Zoop\Exceptions\ZoopException("Cartão não encontrado", 1);

            //cria transação na ZOOP
            $zoopTransaction = ZoopChargesCNP::create([
                'payment_type' => 'credit',
                'on_behalf_of' => Settings::findByKey('zoop_seller_id'), //vendedor admin
                'customer' => $card->customer, //comprador
                'source' => array(
                    'currency' => 'BRL',
                    'amount' => floor($amount * 100),
                    'description' => $description,
                    'usage' => 'single_use',
                    'type' => 'card',
                    'capture' => boolval($capture),
                    'card' => $card
                ),
            ]);

            \Log::debug("[charge]parameters:" . print_r($zoopTransaction, 1));

            if ($zoopTransaction->status == self::ZOOP_FAILED) {
                return array(
                    "success" => false,
                    "type" => 'api_charge_error',
                    "code" => 'api_charge_error',
                    "message" => trans("paymentError.refused"),
                    "transaction_id" => $zoopTransaction->id
                );
            }

            return array(
                'success' => true,
                'captured' => $capture,
                'paid' => ($zoopTransaction->status == self::ZOOP_SUCCEEDED),
                'status' => $zoopTransaction->status,
                'transaction_id' => $zoopTransaction->id
            );
        } catch (Zoop\Exceptions\ZoopException $ex) {
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

    /**
	 * Função para gerar boletos de pagamentos
	 * @param int $amount valor do boleto
	 * @param User/Provider $client instância do usuário ou prestador
	 * @param string $postbackUrl url para receber notificações do status do pagamento
	 * @param string $billetExpirationDate data de expiração do boleto
	 * @param string $billetInstructions descrição no boleto
	 * @return array
	 */
    public function billetCharge($amount, $client, $postbackUrl = null, $billetExpirationDate, $billetInstructions = "")
    {
        try {
            
            //recupera comprador na zoop
            $customer = $this->findCustomerAll($client);

            //cria comprador na zoop caso não exista
            if ($customer == null)
                $customer_id = $this->createCustomer($client);
            else
                $customer_id = $customer->id;

            //cria transação na ZOOP
            $zoopTransaction = ZoopChargesCNP::create([
                        'amount' => floor($amount * 100),
                        'currency' => 'BRL',
                        'description' => self::ZOOP_BILLET_CHARGE,
                        'payment_type' => 'boleto',
                        'capture' => true,
                        'on_behalf_of' => Settings::findByKey('zoop_seller_id'),
                        'customer' => $customer_id,
                        'payment_method' => array(
                            'expiration_date' => $billetExpirationDate,
                            'top_instructions' => array($billetInstructions)
                        ),
                        'source' => array('currency' => 'BRL',
                            'amount' => floor($amount * 100),
                            'description' => self::ZOOP_BILLET_CHARGE,
                            'usage' => 'single_use',
                            'type' => 'customer'
                        ),
            ]);

            \Log::debug("[charge]parameters:" . print_r($zoopTransaction, 1));

            if ($zoopTransaction->status == self::ZOOP_FAILED) {
                return array(
                    "success" => false,
                    "type" => 'api_charge_error',
                    "code" => 'api_charge_error',
                    "message" => trans("paymentError.refused"),
                    "transaction_id" => $zoopTransaction->id
                );
            }

            return array(
                'success' => true,
                'captured' => true,
                'paid' => ($zoopTransaction->status == self::ZOOP_SUCCEEDED),
                'status' => self::ZOOP_WAITING_PAYMENT,
                'transaction_id' => $zoopTransaction->id,
                'billet_url' => $zoopTransaction->payment_method->url,
                'billet_expiration_date' => $zoopTransaction->payment_method->expiration_date
            );
        } catch (Zoop\Exceptions\ZoopException $ex) {
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

    /**
	 * Trata o postback retornado pelo gateway
	 */
	public function billetVerify ($request, $transaction_id = null)
	{
		$postbackTransaction = $request->payload;
        
		if (!$postbackTransaction)
			return [
				'success' => false,
				'status' => '',
				'transaction_id' => ''
			];

		return [
			'success' => true,
			'status' => $postbackTransaction->status == self::ZOOP_SUCCEEDED ? "paid" : $postbackTransaction->status,
			'transaction_id' => $postbackTransaction->id
		];
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

    public function chargeWithSplit(Payment $payment, Provider $provider, $totalAmount, $providerAmount, $description, $capture = true, User $user = null)
    {

        try {

            //ajusta valores para o admin e para o prestador
            $admin_value = $totalAmount - $providerAmount;
            $admin_value = round($admin_value * 100);
            $providerAmount = round($providerAmount * 100);

            if ($admin_value + $providerAmount == (round($totalAmount * 100)))
                $totalAmount = round($totalAmount * 100);
            else if ($admin_value + $providerAmount == (ceil($totalAmount * 100)))
                $totalAmount = ceil($totalAmount * 100);
            else if ($admin_value + $providerAmount == (floor($totalAmount * 100)))
                $totalAmount = floor($totalAmount * 100);

            //Recupera vendedor admin na ZOOP
            $seller_adm = ZoopSellers::get(Settings::findByKey('zoop_seller_id'));

            if ($seller_adm == null)
                throw new Zoop\Exceptions\ZoopException("Recebedor do Administrador não foi encontrado. Corrigir no sistema Web.", 1);

            //Recupera conta do prestador
            $bank_account = LedgerBankAccount::where("provider_id", "=", $provider->id)->first();

            if ($bank_account == null)
                throw new Zoop\Exceptions\ZoopException("Conta do prestador nao encontrada.", 1);

            //Recupera vendedor na ZOOP do prestador
            $recipient = ZoopSellers::get($bank_account->recipient_id);

            if ($recipient == null)
                throw new Zoop\Exceptions\ZoopException("Recebedor não foi encontrado", 1);


            //Recupera cartão do comprador
            $card = ZoopCards::get($payment->card_token);

            if ($card == null)
                throw new Zoop\Exceptions\ZoopException("Cartão não encontrado", 1);

            //Cria transação na ZOOP
            $zoopTransaction = ZoopChargesCNP::create([
                'payment_type' => 'credit',
                'on_behalf_of' => Settings::findByKey('zoop_seller_id'), //vendedor admin
                'customer' => $card->customer, //comprador
                'source' => array(
                    'currency' => 'BRL',
                    'amount' => $totalAmount,
                    'description' => $description,
                    'usage' => 'single_use',
                    'type' => 'card',
                    'capture' => boolval($capture),
                    'card' => $card
                ),
            ]);

            //Faz split com o prestador na transação
            $zoopSplit = ZoopSplitTransactions::create($zoopTransaction->id, [
                'recipient' => isset($recipient->items) ? $recipient->items[0]->id : $recipient->id,
                'amount' => $providerAmount,
                "charge_processing_fee" => self::getReversedProcessingFeeCharge() ? true : false,
                "liable" => true, //assume risco de transação (possíveis estornos)
            ]);

            \Log::debug("[charge]response: zoopTransaction" . print_r($zoopTransaction, 1));
            \Log::debug("[charge]response: zoopSplit" . print_r($zoopSplit, 1));

            if ($zoopTransaction->status == self::ZOOP_FAILED) {
                return array(
                    "success" => false,
                    "type" => 'api_charge_error',
                    "code" => 'api_charge_error',
                    "message" => trans("paymentError.refused"),
                    "transaction_id" => $zoopTransaction->id
                );
            }

            return array(
                'success' => true,
                'captured' => $capture,
                'paid' => ($zoopTransaction->status == self::ZOOP_SUCCEEDED),
                'status' => $zoopTransaction->status,
                'transaction_id' => $zoopTransaction->id
            );
        } catch (Zoop\Exceptions\ZoopException $ex) {
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

    /* Método para retornar comprador padrão ZOOP
     * @param $payment Payment - instância do pagamento com dados do cartão
     * @return array
     */

    private function getCustomer(Payment $payment = null, $user = null)
    {

    if ($payment)
        $user = $payment->User;

        $customer = array(
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone_number' => $user->phone,
            'taxpayer_id' => $user->document,
            'birthdate' => $user->birthdate,
            'description' => "Customer for " . $user->email,
            'address' => array(
                'line1' => $user->address . ", " . $user->address_number,
                'neighborhood' => $user->address_neighbour,
                'city' => $user->address_city,
                'state' => $user->state,
                'postal_code' => $user->zipcode,
                'country_code' => 'BR'
            )
        );

        return $customer;
    }

    /* Método para capturar transação do comprador sem repassar valor algum ao prestador
     * @param $transaction Transaction - instância do transação
     * @param $amount Double - valor a ser transacionado
     * @param $payment Payment - instância do pagamento com dados do cartão
     * @return array
     */

    public function capture(Transaction $transaction, $amount, Payment $payment = null)
    {

        try {

            $amount *= 100;

            //Recupera transação na ZOOP
            $zoopTransaction = ZoopChargesCNP::get($transaction->gateway_transaction_id);

            if ($zoopTransaction == null)
                throw new Zoop\Exceptions\ZoopException("Transaction not found.", 1);

            //verificar se valor passado é maior que valor registrado na zoop
            if ($amount > $zoopTransaction->amount)
                $amount = $zoopTransaction->amount;

            \Log::debug("[capture]parameters:" . print_r($zoopTransaction, 1));

            //Captura a partir de transação já existente
            $zoopTransactionCapture = ZoopChargesCNP::capture($zoopTransaction->id, [
                'on_behalf_of' => Settings::findByKey('zoop_seller_id'), //vendedor admin
                'amount' => floor($amount),
            ]);

            \Log::debug("[capture]response:" . print_r($zoopTransactionCapture, 1));

            return array(
                'success' => true,
                'status' => $zoopTransactionCapture->status,
                'captured' => ($zoopTransactionCapture->status == self::ZOOP_SUCCEEDED),
                'paid' => ($zoopTransactionCapture->status == self::ZOOP_SUCCEEDED),
                'transaction_id' => $zoopTransactionCapture->id
            );
        } catch (Zoop\Exceptions\ZoopException $ex) {
            \Log::error($ex->getMessage());

            return array(
                "success" => false,
                "type" => 'api_capture_error',
                "code" => $ex->getReturnCode(),
                "message" => trans("paymentError." . $ex->getReturnCode()),
                "transaction_id" => $transaction->gateway_transaction_id
            );
        }
    }

    /* Método para realizar reembolso da transação
     * @param $transaction Transaction - instância da transação
     * @param $payment Payment - instância do pagamento
     * @return array
     */

    public function refund(Transaction $transaction, Payment $payment)
    {

        //verifica se a transação já foi reembolsada
        if ($transaction && $transaction->status != Transaction::REFUNDED) {

            try {

                //Realiza reembolso da transação
                $refund = ZoopChargesCNP::cancel($transaction->gateway_transaction_id, [
                    'on_behalf_of' => Settings::findByKey('zoop_seller_id'),
                    'amount' => floor($transaction->gross_value),
                ]);

                \Log::debug("[refund]response:" . print_r($refund, 1));

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

    public function refundWithSplit(Transaction $transaction, Payment $payment)
    {

        \Log::debug('refund with split');

        //chama reembolso padrão
        return ($this->refund($transaction, $payment));
    }

    /* Método para realizar captura da transação do comprador com repasse ao prestador
     * @param $transaction Transaction - instância do transação
     * @param $provider Provider - instância do prestador
     * @param $totalAmount Double - valor total a ser transacionado
     * @param $providerAmount Double - valor do prestador a ser transacionado
     * @param $payment Payment - instância do pagamento com dados do cartão
     * @return array
     */

    public function captureWithSplit(Transaction $transaction, Provider $provider, $totalAmount, $providerAmount, Payment $payment = null)
    {

        try {

            \Log::debug('capture with split');

            //define valor do admin a partir do valor total e valor do prestador
            $adminAmount = $totalAmount - $providerAmount;

            //Recupera transação na ZOOP
            $zoopTransaction = ZoopChargesCNP::get($transaction->gateway_transaction_id);

            if ($zoopTransaction == null)
                throw new Zoop\Exceptions\ZoopException("Transaction not found.", 1);

            //Realiza captura a partir de transação já com split
            $zoopTransactionCapture = ZoopChargesCNP::capture($zoopTransaction->id, [
                'on_behalf_of' => Settings::findByKey('zoop_seller_id'), //vendedor admin
                'amount' => floor($adminAmount),
            ]);

            \Log::debug("[capture]response:" . print_r($zoopTransactionCapture, 1));

            return array(
                'success' => true,
                'status' => $zoopTransactionCapture->status,
                'captured' => ($zoopTransactionCapture->status == self::ZOOP_SUCCEEDED),
                'paid' => ($zoopTransactionCapture->status == self::ZOOP_SUCCEEDED),
                'transaction_id' => $zoopTransactionCapture->id
            );
        } catch (Zoop\Exceptions\ZoopException $ex) {
            \Log::error($ex->getMessage());

            return array(
                "success" => false,
                "type" => 'api_capture_error',
                "code" => $ex->getReturnCode(),
                "message" => trans("paymentError." . $ex->getReturnCode()),
                "transaction_id" => $transaction->gateway_transaction_id
            );
        }
    }

    /* Método para recuperar transação na ZOOP
     * @param $transaction Transaction - instância da transação
     * @param $payment Payment - instância do pagamento
     * @return array
     */

    public function retrieve(Transaction $transaction, Payment $payment = null)
    {

        //Recupera transação na ZOOP
        $zoopTransaction = ZoopChargesCNP::get($transaction->gateway_transaction_id);

        return array(
            'success' => true,
            'transaction_id' => $zoopTransaction->id,
            'amount' => $zoopTransaction->amount,
            'destination' => '',
            'status' => $zoopTransaction->status,
            'card_last_digits' => $zoopTransaction->payment_method->last4_digits,
        );
    }

    /* Método para criar ou atualizar conta na ZOOP - com vendedor
     * @param $ledgerBankAccount LedgerBankAccount - instância da conta do vendedor
     * @return array
     */

    public function createOrUpdateAccount(LedgerBankAccount $ledgerBankAccount)
    {

        $return = [];
        $recipient = null;

        $bank = Bank::where('id', $ledgerBankAccount->bank_id)->first();
        $provider = Provider::find($ledgerBankAccount->provider_id);

        //Se o recipient_id for diferente de null e diferente de empty (padrão do sistema), retorna vendedor. Senão, cria novo vendedor.
        if ($ledgerBankAccount->recipient_id && $ledgerBankAccount->recipient_id != "empty") {
            $recipient = ZoopSellers::get($ledgerBankAccount->recipient_id);
        } else {
            $ledgerBankAccount->recipient_id = null;
        }
        try {

            //define dados para a conta
            $bankAccount = array(
                "holder_name" => $ledgerBankAccount->holder,
                "bank_code" => $bank->code,
                "routing_number" => $ledgerBankAccount->agency,
                "account_number" => $ledgerBankAccount->account . $ledgerBankAccount->account_digit,
                "taxpayer_id" => $recipient ? $recipient->taxpayer_id : $ledgerBankAccount->document,
                "type" => ($ledgerBankAccount->account_type == "conta_corrente" ? "checking" : ($ledgerBankAccount->account_type == "conta_poupanca" ? "savings" : "")),
            );

            //Cria ou atualiza conta se o taxpayer_id for o mesmo
            $zoopBank = ZoopTokens::tokenizeBankAccount($bankAccount);

            //Cria um vendedor na ZOOP por não existir
            if (!$recipient) {

                //define dados do vendedor
                $dataSeller = array(
                    'first_name' => $provider->first_name,
                    'last_name' => $provider->last_name,
                    'email' => $provider->email,
                    'phone_number' => $provider->phone,
                    'description' => "Seller for " . $provider->email,
                    'type' => 'individual',
                    'taxpayer_id' => $ledgerBankAccount->document,
                    'marketplace_id' => Settings::findByKey('zoop_marketplace_id'),
                    'default_credit' => $zoopBank->bank_account->id, //id conta
                    'address' => array(
                        'line1' => $provider->address . ", " . $provider->address_number,
                        'neighborhood' => $provider->address_neighbour,
                        'city' => $provider->address_city,
                        'state' => $provider->state,
                        'postal_code' => $provider->zipcode
                    ),
                );

                //Cria um vendedor na ZOOP
                $seller = ZoopSellers::createIndividuals($dataSeller);
                //------------------
                \Log::info("[Zoop_Recipient] Saida: " . print_r($seller, 1));

                //Registra novo recipient_id no sistema
                $ledgerBankAccount->recipient_id = $seller->id;
                $ledgerBankAccount->save();
            }

            //se já tiver vendedor, retorna
            if ($recipient) {
                $return['recipient_id'] = $recipient->id;
            } else {
                $return['recipient_id'] = $seller->id;
            }

            return array(
                "success" => true,
                "recipient_id" => $return['recipient_id']
            );
        } catch (\Zoop\Exceptions\ZoopException $ex) {

            \Log::error($ex->__toString());

            $return = array(
                "success" => false,
                "recipient_id" => 'empty',
                "type" => 'api_bankaccount_error',
                "code" => $ex->getReturnCode(),
                "message" => trans("empty." . $ex->getMessage())
            );

            return $return;
        }
    }

    /* Método para deletar cartão na ZOOP
     * @param $payment Payment - instância do pagamento com cartão
     * @return array
     */

    public function deleteCard(Payment $payment, User $user = null)
    {

        try {

            //Recupera cartão
            $card = ZoopCards::get($payment->card_token);

            if ($card == null)
                throw new Zoop\Exceptions\ZoopException("Cartão não encontrado", 1);

            //Deleta cartão
            ZoopCards::delete($card->id);

            return array(
                "success" => true
            );
        } catch (\Zoop\Exceptions\ZoopException $ex) {
            $body = $ex->getJsonBody();
            $error = $body['error'];

            if (array_key_exists('code', $body))
                $code = $body["code"];
            else
                $code = null;

            return array(
                "success" => false,
                'data' => null,
                'error' => array(
                    "code" => ApiErrors::CARD_ERROR,
                    "messages" => array(trans('creditCard.' . $error["code"]))
                )
            );
        }
    }

    /* Método para criar o comprador na ZOOP
     * @param $user User - instância do usuário comprador
     * @return array
     */

    private function createCustomer($user)
    {

        //define dados do comprador e cria na zoop
        $customer = ZoopBuyers::create([
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone_number' => $user->phone,
            'taxpayer_id' => $user->document,
            'birthdate' => $user->birthdate,
            'description' => "Customer for " . $user->email,
            'address' => array(
                'line1' => $user->address . ", " . $user->address_number,
                'neighborhood' => $user->address_neighbour,
                'city' => $user->address_city,
                'state' => $user->state,
                'postal_code' => $user->zipcode,
                'country_code' => 'BR'
            ),
        ]);

        return $customer->id;
    }

    /* Método para procurar e retorna o comprador na base de dados da ZOOP
     * @param $user User - instância do usuário comprador
     * @return array
     */

    private function findCustomerAll($user)
    {

        $res = ZoopBuyers::getAll();

        foreach ($res->items as $customer) {

            if ($user->email == $customer->email && $user->document == $customer->taxpayer_id) {

                return $customer;
            }
        }

        return null;
    }

    /* Método para procurar e retorna o comprador na base de dados da ZOOP de forma específica
     * @param $user User - instância do usuário comprador
     * @return array
     */

    private function findCustomer($user)
    {

        try {
            $res = ZoopBuyers::get('search?taxpayer_id=' . $user->document . '&email=' . $user->email);
            return $res;
        } catch (Zoop\Exceptions\ZoopException $ex) {

            \Log::error($ex->getMessage());

            return array(
                "success" => false,
                "type" => $ex->getMessage(),
                "code" => $ex->getReturnCode(),
                "message" => trans("paymentError." . $ex->getReturnCode()),
            );
        }
    }

    /* Método para associar o cartão criado ao comprador existente ou novo
     * @param $token_id - token da requisição
     * @param $customer_id - id do comprador
     * @return array
     */

    private function associateWithACustomer($token_id, $customer_id)
    {

        $associate = ZoopCards::associateWithACustomer([
            'token' => $token_id,
            'customer' => $customer_id,
        ]);

        return $associate;
    }

    public function getNextCompensationDate()
    {
        $carbon = Carbon::now();
        $carbon->addDays(31);
        return $carbon;
    }

    public static function getRecipientId()
    {
        return Settings::findByKey('zoop_seller_id'); //vendedor admin
    }

    public function getGatewayTax()
    {
        return 0.0399;
    }

    public function getGatewayFee()
    {
        return 0.5;
    }

    private static function getReversedProcessingFeeCharge()
    {
        return true;
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

    public static function getSellerStatusBySellerId($id){
        if ($id) {
            try {
                $seller_adm = ZoopSellers::get($id);
            } catch (\Throwable $th) {
                $seller_adm =   null;
            }
            
        } else {
            $seller_adm = null;
        }
        
        if ($seller_adm) {
            return $seller_adm->status;
        } else {
            return null;
        }
        
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
}