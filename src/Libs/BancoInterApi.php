<?php
namespace Codificar\PaymentGateways\Libs;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class BancoInterApi{

    const POST_REQUEST      = 'POST';
    const GET_REQUEST       = 'GET';
    const PUT_REQUEST       = 'PUT';

    const URL_BILLET        = 'https://apis.bancointer.com.br/openbanking/v1/certificado/boletos';
    const URL_BAIXAS        = '/baixas';
    const URL_PDF           = '/pdf';

    const APP_TIMEOUT = 200;
    const ROUND_VALUE = 100;

    public static function apiRequest($url, $fields, $header, $requestType)
    {
        try
        {
            $session = curl_init();

            curl_setopt($session, CURLOPT_URL, $url);
            curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($session, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($session, CURLOPT_CUSTOMREQUEST, $requestType );
            
            if ($fields) {
                curl_setopt($session, CURLOPT_POSTFIELDS, ($fields));
            } else {
                array_push($header, 'Content-Length: 0');
                // curl_setopt($session, CURLOPT_POSTFIELDS, json_encode(array()));
            }
            
            curl_setopt($session, CURLOPT_TIMEOUT, self::APP_TIMEOUT);
            curl_setopt($session, CURLOPT_HTTPHEADER, $header);            

            $msg_chk = curl_exec($session);  
            
            $httpcode = curl_getinfo($session, CURLINFO_HTTP_CODE);  

            $result = json_decode($msg_chk);
            
            if ($httpcode == 200 ||$httpcode ==  201 ||$httpcode ==  202) {
                return (object)array (
                    'success'           =>  true,
                    'data'              =>  $result
                );
            } else {
                throw new Exception(
                    $msg_chk
                );
                \Log::error('Error message Exception: '.$msg_chk);
            }            

        }
        catch(Exception  $ex)
        {
            $return = (object)array(
                "success" 					=> false ,
                // "transaction_id"            => $result->paymentToken,
                "message" 					=> $ex->getMessage()
            );
            
            \Log::error(($ex));

            return $return;
        }
    }

    /**
	 * Função para gerar boletos de pagamentos
	 * @param int $amount valor do boleto
	 * @param User/Provider $client instância do usuário ou prestador
	 * @param string $postbackUrl url para receber notificações do status do pagamento
	 * @param string $boletoExpirationDate data de expiração do boleto
	 * @param string $boletoInstructions descrição no boleto
	 * @return array
	 */
    public static function billetCharge ($amount, $client, $postbackUrl, $boletoExpirationDate, $boletoInstructions)
    {
        $url = self::URL_BILLET;
        $orderId = self::getOrderId(Carbon::now()->toDateTimeString());

        $discount = array(
            "codigoDesconto"    => "NAOTEMDESCONTO",
            "taxa"              => 0,
            "valor"             => 0,
            "data"              => ""
        );

        $cnpj= Settings::findObjectByKey('cnpj_for_banco_inter');

        $seuNumero = date('Y').date('W').sprintf('%05d', $client->id);

        $expirationDate = date('Y-m-d', strtotime($boletoExpirationDate));
        $oneDayAfter = new DateTime($expirationDate)->add(new DateInterval('P1D'))->format('Y-m-d');

        $fields = array(
            "pagador"           => $this->getBilletPagador($client),
            
            "cnpjCPFBeneficiario"   => $cnpj->value,
            "seuNumero"             => $seuNumero,
            "dataEmissao"           => date('Y-m-d'),
            "dataVencimento"        => $expirationDate,
            "numDiasAgenda"         => "SESSENTA",

            "mensagem"          => array(
                "linha1"        => $boletoInstructions
            ),

            "valorNominal"      => self::amountRound($amount),
            "valorAbatimento"   => 0,

            "desconto1"         => $discount,
            "desconto2"         => $discount,
            "desconto3"         => $discount,

            "multa"             => array(
                "codigoMulta"   => "PERCENTUAL",
                "taxa"          => 2,
                "data"          => $oneDayAfter
            ),
               
            "mora"              => array(
                "codigoMora"    => "TAXAMENSAL",
                "taxa"          => 1,
                "data"          => $oneDayAfter
            ),  
        );
        
        $body = json_encode($fields);

        $account = Settings::findObjectByKey('banco_inter_account');
        $header = [
            'accept: application/json',
            'content-type: application/json',
            'x-inter-conta-corrente: '.$account->value,
        ];

        $requestType = self::POST_REQUEST;
        $apiRequest = self::apiRequest($url, $body, $header, $requestType);

        $response = $apiRequest;

        $response->expirationDate = $expirationDate;
        $response->url = self::createBilletPdf($apiRequest->nossoNumero);

        return $response;
    }

    public static function retrieve($transaction)
    {
        $transactionToken = $transaction->gateway_transaction_id;

        $url = sprintf('%s/%s', self::URL_BILLET, $transactionToken);

        $body = null;

        $account = Settings::findObjectByKey('banco_inter_account');
        $header = [
            'accept: application/json',
            'content-type: application/json',
            'x-inter-conta-corrente: '.$account->value,
        ];

        $requestType = self::GET_REQUEST;
        $apiRequest = self::apiRequest($url, $body, $header, $requestType);

        $response = $apiRequest;
        $response->transaction_id = $transactionToken;

        return $response;
    }

    /**
	 * Formata informações do pagador para gerar o boleto
	 */
	private function getBilletPagador($client)
	{
		$zipcode = $client->getZipcode();
		$zipcode = self::cleanWord($zipcode);

        $docType = strlen($client->getDocument()) > 11 ? "JURIDICA" : "FISICA";

		$pagador = array(
            "cnpjCpf"       => $client->document,
            "nome"          => $client->getFullName(),
            "email"         => $client->email,
            "telefone"      => $client->getPhoneNumber(),
            "cep"           => $zipcode,
            "numero"        => formatPhone($client->getStreetNumber()),
            "complemento"   => $client->address_complements,
            "bairro"        => $client->getNeighborhood(),
            "cidade"        => $client->address_city,
            "uf"            => $client->state,
            "endereco"      => $client->getStreet(),
            "ddd"           => $client->getLongDistance(),
            "tipoPessoa"    => $docType
		);

		return $pagador;
	}

    private static function cleanWord($word)
	{
		$word = str_replace(".", "", $word);
		$word = str_replace("-", "", $word);
		$word = str_replace("/", "", $word);
		$word = str_replace("/n", "", $word);

		return $word;
	}

    private static function formatPhone($phone)
    {
        $phone = str_replace('(', '', $phone);
        $phone = str_replace(')', '', $phone);
        $phone = str_replace('-', '', $phone);
        $phone = str_replace('+', '', $phone);
        $phone = str_replace(' ', '', $phone);
        $phone = substr($phone, -11);

        return $phone;
    }

    private static function amountRound($amount)
    {
        $amount = $amount * self::ROUND_VALUE;
        $type = gettype($amount);
        $amount = (int)$amount;

        return $amount;
    }

    private static function createBilletPdf($nossoNumero)
    {
        $url = self::URL_BILLET . $nossoNumero . self::URL_PDF;

        $body = null;

        $account = Settings::findObjectByKey('banco_inter_account');
        $header = [
            'accept: application/json',
            'content-type: application/json',
            'x-inter-conta-corrente: '.$account->value,
        ];

        $requestType = self::GET_REQUEST;
        $apiRequest = self::apiRequest($url, $body, $header, $requestType);

        $pdf_decoded = base64_decode ($apiRequest);
        Storage::put('storage/billets/'.$nossoNumero.'.pdf', $pdf_decoded);

        return route("billetPdf", $nossoNumero);
    }
}
