<?php
namespace Codificar\PaymentGateways\Libs;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

use Log, Exception;
use Settings;
use DateTime;
use DateInterval;

class BancoInterApi{

    const POST_REQUEST      = 'POST';
    const GET_REQUEST       = 'GET';

    const URL_BILLET        = 'https://apis.bancointer.com.br:8443/openbanking/v1/certificado/boletos';
    const URL_BAIXAS        = '/baixas';
    const URL_PDF           = '/pdf';

    const APP_TIMEOUT = 200;

    public static function apiRequest($url, $fields, $header, $requestType, $isPdf = false)
    {
        try
        {
            $crt_file = getcwd() .'/..'. Storage::url('app/certificates/BancoInterCertificate.pem');
            $key_file = getcwd() .'/..'. Storage::url('app/certificates/BancoInterKey.pem');

            $session = curl_init();

            curl_setopt($session, CURLOPT_SSLCERT, $crt_file);
            curl_setopt($session, CURLOPT_SSLKEY, $key_file);

            curl_setopt($session, CURLOPT_URL, $url);
            curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($session, CURLOPT_VERBOSE, true);
            curl_setopt($session, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($session, CURLOPT_SSL_VERIFYPEER, true);
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

            if($isPdf){
                $result = $msg_chk;
            }else{
                $result = json_decode($msg_chk);
            }
            
            if ($httpcode == 200 ||$httpcode ==  201 ||$httpcode ==  202) {
                return (object) array (
                    'success'           =>  true,
                    'data'              =>  $result
                );
            } else {
                \Log::error($url);
                \Log::error($httpcode);
                \Log::error($msg_chk);
                throw new Exception(
                    $msg_chk
                );
            }            

        }
        catch(Exception $ex)
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

        $discount = array(
            "codigoDesconto"    => "NAOTEMDESCONTO",
            "taxa"              => 0,
            "valor"             => 0,
            "data"              => ""
        );

        $cnpj = Settings::findObjectByKey('cnpj_for_banco_inter');

        $seuNumero = date('Y').date('W').sprintf('%05d', $client->id);

        $expirationDate = date('Y-m-d', strtotime($boletoExpirationDate));
        $oneDayAfter = date('Y-m-d', strtotime($expirationDate . ' +1 day'));

        $fields = array(
            "pagador"           => self::getBilletPagador($client),
            
            "cnpjCPFBeneficiario"   => $cnpj->value,
            "seuNumero"             => $seuNumero,
            "dataEmissao"           => date('Y-m-d'),
            "dataVencimento"        => $expirationDate,
            "numDiasAgenda"         => "SESSENTA",

            "mensagem"          => array(
                "linha1"        => $boletoInstructions
            ),

            "valorNominal"      => $amount,
            "valorAbatimento"   => 0,

            "desconto1"         => $discount,
            "desconto2"         => $discount,
            "desconto3"         => $discount,

            "multa"             => array(
                "codigoMulta"   => "PERCENTUAL",
                "taxa"          => 2,
                "valor"         => 0,
                "data"          => $oneDayAfter
            ),
               
            "mora"              => array(
                "codigoMora"    => "TAXAMENSAL",
                "taxa"          => 1,
                "valor"         => 0,
                "data"          => $oneDayAfter
            )
        );
        $body = json_encode($fields);

        $header = self::getHeaders();

        $requestType = self::POST_REQUEST;
        $apiRequest = self::apiRequest($url, $body, $header, $requestType);


        if($apiRequest->success == true){
            $response = $apiRequest->data;
    
            $response->expirationDate = $expirationDate;
            $response->url = self::createBilletPdf($response->nossoNumero);

            return $response;
        }

        return false;
    }

    public static function retrieve($transaction)
    {
        $transactionToken = $transaction->gateway_transaction_id;

        $url = sprintf('%s/%s', self::URL_BILLET, $transactionToken);

        $body = null;

        $header = self::getHeaders();

        $requestType = self::GET_REQUEST;
        $apiRequest = self::apiRequest($url, $body, $header, $requestType);

        if($apiRequest->success == true){
            $response = $apiRequest->data;
            $response->transaction_id = $transactionToken;
        }

        return $response;
    }

    /**
	 * Formata informações do pagador para gerar o boleto
	 */
	private static function getBilletPagador($client)
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
            "numero"        => self::formatPhone($client->getStreetNumber()),
            "complemento"   => $client->address_complements,
            "bairro"        => $client->getNeighborhood(),
            "cidade"        => $client->address_city,
            "uf"            => $client->state,
            "endereco"      => $client->getStreet(),
            "ddd"           => (string)$client->getLongDistance(),
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

    public static function createBilletPdf($nossoNumero)
    {
        $url = self::URL_BILLET .'/'. $nossoNumero . self::URL_PDF;

        $body = null;

        $header = self::getHeaders(true);

        $requestType = self::GET_REQUEST;
        $apiRequest = self::apiRequest($url, $body, $header, $requestType, true);

        $pdf_decoded = base64_decode($apiRequest->data);
        
        Storage::put('billets/'.$nossoNumero.'.pdf', $pdf_decoded);

        return route("billetPdf", $nossoNumero);
    }

    private static function getHeaders($isPdf = false){
        $account = Settings::findObjectByKey('banco_inter_account');
        if($isPdf){
            $header = array(
                'content-type: application/json',
                'content-type: application/base64',
                'x-inter-conta-corrente: '.$account->value
            );
        }else{
            $header = array(
                'accept: application/json',
                'content-type: application/json',
                'x-inter-conta-corrente: '.$account->value
            );
        }
        return $header;
    }
}
