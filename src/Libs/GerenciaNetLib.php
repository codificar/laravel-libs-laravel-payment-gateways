<?php

namespace Codificar\PaymentGateways\Libs;

use Exception;
use Gerencianet\Exception\GerencianetException;
use Gerencianet\Gerencianet;
use Illuminate\Support\Facades\Validator;

//models do sistema
use Payment;
use Provider;
use Transaction;
use User;
use LedgerBankAccount;
use Settings;

/**
 * Class GerenciaNetLib
 *
 * @package MotoboyApp
 *
 * @author  Álvaro Oliveira <alvaro.oliveira@codificar.com.br>
 */
class GerenciaNetLib implements IPayment
{

	//Para buscar chaves no banco
	const GERENCIANET_CLIENT_ID = 'gerencianet_client_id';
	const GERENCIANET_CLIENT_SECRET = 'gerencianet_client_secret';
	const GERENCIANET_SANDBOX = 'gerencianet_sandbox';
	const NOTIFICATION_URL = '/notifications/gerencianet/billet';
	const MIN_PHONE = 10;

	//Armaneza chaves
	public $options;
	private $api;
	// payment_token obtido na 1ª etapa (através do Javascript único por conta Gerencianet)
	private $paymentToken = 'deb93db426e85a08611edff1fc566d47f6ef9668';

	//Define split automático com provider
	const AUTO_TRANSFER_PROVIDER = 'auto_transfer_provider_payment';

	public function __construct()
	{
		$this->setApiKey();
	}

	/**
	 * Método para setar chaves da GerenciaNet para requisições
	 * @return void
	 */
	private function setApiKey()
	{
		$isSandbox = Settings::findByKey(self::GERENCIANET_SANDBOX);
		$isSandbox = ($isSandbox == "false" || $isSandbox == false) ? false : true;
		$this->options = [
			'client_id' => Settings::findByKey(self::GERENCIANET_CLIENT_ID),
			'client_secret' => Settings::findByKey(self::GERENCIANET_CLIENT_SECRET),
			'sandbox' => $isSandbox
		];
	}

	private function getApi()
	{
		if (!$this->api) {
			$this->api = new Gerencianet($this->options);
		}
		return $this->api;
	}

	public function createCard(Payment $payment, User $user = null)
	{
	}

	/**
	 * Método para realizar cobrança via boleto
	 * @param $payment Payment - instância do pagamento com dados do cartão
	 * @param $items - Itens do boleto (formato dos itens ['name' => , 'amount' => 1, 'value' => (inteiro representando centavos)])
	 * @param $user User - usuário da transação
	 * @param $expire - data de vencimento da fatura
	 * @param $message - mensagem opcional a ser apresentada no boleto
	 * @return array
	 */
	public function invoiceBilletCharge(Payment $payment = null, $items, User $user = null, $expire, $message = '', $invoice_id)
	{
		// $discount = [ // configuração de descontos
		// 	'type' => 'currency', // tipo de desconto a ser aplicado
		// 	'value' => 599 // valor de desconto 
		// ];
		// $configurations = [ // configurações de juros e mora
		// 	'fine' => 200, // porcentagem de multa
		// 	'interest' => 33 // porcentagem de juros
		// ];
		// $conditional_discount = [ // configurações de desconto condicional
		// 	'type' => 'percentage', // seleção do tipo de desconto 
		// 	'value' => 500, // porcentagem de desconto
		// 	'until_date' => '2019-08-30' // data máxima para aplicação do desconto
		// ];
		$valid = $this->validateData($user->document, $expire);
		
		if ($valid['success']) {
			try {
				$data = [
					'items' => $items,
					'payment' => $this->formatBillet($user, $payment, $expire, $message)
				];

				if (self::notificationURL()) {
					$data['metadata'] = [
						'notification_url' => self::notificationURL(),
						'custom_id' => '' . $invoice_id
					];
				}

				$pay_charge = $this->getApi()->oneStep([], $data);

				return $pay_charge;
			} catch (GerencianetException $e) {
				return [
					"code" => $e->code,
					"error_code" => $e->error,
					"error" => $e->message,
					"description" => $e->message
				];
			} catch (Exception $e) {
				return ['error' => $e->getMessage()];
			}
		} else {
			// dd($valid);
			return $valid;
		}
	}

	/**
	 * TODO Resolver a questão de como obter o payment_token
	 */
	/**
	 * Método para realizar cobrança no cartão do comprador sem repassar valor algum ao prestador
	 * @param $payment Payment - instância do pagamento com dados do cartão
	 * @param $amount Double - valor a ser transacionado
	 * @param $description String - descrição da transação
	 * @param $capture Boolean - definição de captura da transação (não utilizado na Gerencianet)
	 * @param $user User - usuário da transação
	 * @return array
	 */
	public function charge(Payment $payment, $amount, $description, $capture = true, User $user = null)
	{
		return $this->createAndPayCharge([
			'items'		=> $this->formatItems($description, $amount),
			'payment'	=> $this->formatCard($user, $payment, $this->paymentToken)
		]);
	}

	//TODO perguntar como obter "payee_code" do $provider
	public function chargeWithSplit(Payment $payment, Provider $provider, $totalAmount, $providerAmount, $description, $capture = true, User $user = null)
	{
		return $this->createAndPayCharge([
			'items'		=> $this->formatItems($description, $totalAmount, $providerAmount / (float) $totalAmount, '8bf080a031063b9cf99656d33cfbd2fe'),
			'payment'	=> $this->formatCard($user, $payment, $this->paymentToken)
		]);
	}

	private function createAndPayCharge($body)
	{
		try {
			$pay_charge = $this->getApi()->oneStep([], $body);
			$pay_charge['data']['charge_id'];
			return [
				"success" => true,
				"captured" => false,
				"paid" => false,
				"transaction_id" => $pay_charge["data"]["charge_id"],
				"transaction_data" => $pay_charge["data"]
			];
		} catch (GerencianetException $e) {
			return [
				"success" => false,
				"code" => $e->code,
				"error" => $e->error,
				"description" => $e->description
			];
		} catch (Exception $e) {
			return [
				"success" => false,
				"error" => $e->getMessage()
			];
		}
	}

	/**
	 * Método para criar o array de itens exigido pela plataforma.
	 * Observe que o $amount nos parâmetros é o valor total do item, enquanto que o amount na chave do array é a quantidade daquele item
	 * @author Álvaro Oliveira
	 */
	private function formatItems($description, $amount, $percentage = 0, $payeeCode = null)
	{
		$item = [
			'name' => $description,
			'amount' => 1,
			'value' => round($amount * 100)
		];
		if ($percentage != 0) {
			$item['marketplace'] = ['repasses' => [[
				'payee_code' => $payeeCode,
				'percentage' => round($percentage * 10000)
			]]];
		}
		return [$item];
	}

	private function formatBillet($user, $payment, $expire, $message)
	{
		//Recupera user
		$user = ($user) ? $user : $payment->User;
		return [
			'banking_billet' => [
				'expire_at' => $expire, // data de vencimento do titulo (aaaa-mm-dd)
				'message' => $message, // mensagem a ser exibida no boleto
				'customer' => $this->formatCustomer($user, false),
				// 'discount' =>$discount,
				// 'conditional_discount' => $conditional_discount
			]
		];
	}

	private function formatCard($user, $payment, $paymentToken)
	{
		//Recupera user
		$user = ($user) ? $user : $payment->User;
		return [
			'credit_card' => [
				'customer' => $this->formatCustomer($user),
				'installments' => 1, // número de parcelas em que o pagamento deve ser dividido
				// 'discount' =>$discount,
				'billing_address' => $this->formatAddress($user),
				'payment_token' => $paymentToken,
				'message' => 'teste\nteste\nteste\nteste'
			]
		];
	}

	private function formatCustomer($user, $fisical_person = true)
	{
		$phone = (string) preg_replace('/[^0-9]/', '', $user->getPhone());

		if(strlen($phone) < self::MIN_PHONE ) {
			$phone = str_pad($phone, self::MIN_PHONE, '9', STR_PAD_LEFT);
		}
		//Se for maior que 11, remove o codigo do pais (primeiros 2 caracteres, no brasil e 55)
		else if (strlen($phone) > 11) {
			$phone = substr($phone, 2);
		}
		if ($fisical_person) {
			return [
				'name' => $user->getFullName(),
				'cpf' => $user->document,
				'phone_number' => $phone,
				//'phone_number' => substr($user->getPhone(), 3),
				// 'email' => $user->getEmail(),
				// 'birth' => $user->birthdate //Não obrigatório
			];
		} else {
			return [
				'juridical_person' => [
					'corporate_name' => $user->first_name,
					'cnpj' => $user->document,
				],
				'phone_number' => $phone,
				//'phone_number' => substr($user->getPhone(), 3),
			];
		}
	}

	//TODO usar endereço do usuário mesmo
	private function formatAddress($user)
	{
		return [
			'street' => 'Av JK',
			'number' => 909,
			'neighborhood' => 'Bauxita',
			'zipcode' => '35400000',
			'city' => 'Ouro Preto',
			'state' => 'MG'
		];
	}

	public function capture(Transaction $transaction, $amount, Payment $payment = null)
	{
		//
	}
	public function captureWithSplit(Transaction $transaction, Provider $provider, $totalAmount, $providerAmount, Payment $payment = null)
	{
		//
	}

	public function refund(Transaction $transaction, Payment $payment)
	{
		//
	}

	public function refundWithSplit(Transaction $transaction, Payment $payment)
	{
		//
	}

	public function retrieve(Transaction $transaction, Payment $payment = null)
	{
		try {
			$charge = $this->getApi()->detailCharge(["id" => $transaction->gateway_transaction_id], []);
			return [
				"success" => true,
				"charge" => $charge["data"]
			];
		} catch (GerencianetException $e) {
			return [
				"success" => false,
				"code" => $e->code,
				"error" => $e->error,
				"description" => $e->description
			];
		} catch (Exception $e) {
			return [
				"success" => false,
				"error" => $e->getMessage()
			];
		}
	}

	public function deleteCard(Payment $payment, User $user = null)
	{
		//
	}

	public function createOrUpdateAccount(LedgerBankAccount $ledgerBankAccount)
	{
	}

	public function getGatewayFee()
	{
	}

	public function getGatewayTax()
	{
	}

	public function getNextCompensationDate()
	{
	}

	public function checkAutoTransferProvider()
	{
	}


	public function formatItemsFromFinance($finances)
	{
		$items = [];
		foreach ($finances as $finance) {
			$items[] = [
				'name' => $finance->description,
				'amount' => 1,
				'value' => round($finance->value * 100) * -1
			];
		}
		return $items;
	}

	private static function notificationURL()
	{
		return env('APP_URL') ? (env('APP_URL') . self::NOTIFICATION_URL) : null;
	}

	public function getNotification($token)
	{
		try {
			$chargeNotification = $this->getApi()->getNotification(['token' => $token], []);

			$i = count($chargeNotification["data"]);
			return $chargeNotification["data"][$i - 1];
		} catch (GerencianetException $e) {
			return [
				"success" => false,
				"code" => $e->code,
				"error" => $e->error,
				"description" => $e->description
			];
		} catch (Exception $e) {
			return [
				"success" => false,
				"error" => $e->getMessage()
			];
		}
	}

	private function validateData($cnpj, $expire)
	{
		$validator = Validator::make([
			'cnpj' => $cnpj,
			'expire_date' => $expire
		], [
			'cnpj' => 'string|size:14',
			'expire_date' => 'date_format:Y-m-d|after:today'
		]);

		if ($validator->fails()) {
			return [
				'success' => false,
				'messages' => $validator->messages()->all()
			];
		} else {
			return ['success' => true];
		}
	}

	public function settle($charge_id)
	{
		try {
			return $this->getApi()->settleCharge(['id' => $charge_id], []);
		} catch (GerencianetException $e) {
			return [
				"success" => false,
				"code" => $e->code,
				"error" => $e->error,
				"description" => $e->description
			];
		} catch (Exception $e) {
			return [
				"success" => false,
				"error" => $e->getMessage()
			];
		}
	}


	public function billetCharge($amount, $client, $postbackUrl, $billetExpirationDate, $billetInstructions)
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

	public function retrievePix($transaction_gateway_id)
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
