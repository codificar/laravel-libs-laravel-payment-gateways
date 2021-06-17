<?php
namespace Codificar\PaymentGateways\Libs;
use Carbon\Carbon;

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
            }	else {
                array_push($header, 'Content-Length: 0');
                // curl_setopt($session, CURLOPT_POSTFIELDS, json_encode(array()));
            }
            \
            
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

        $discont = array(
            "codigoDesconto"    => "NAOTEMDESCONTO",
            "taxa"              => 0,
            "valor"             => 0,
            "data"              => ""
        );

        $fields = array(
            "pagador"           => $this->getBilletPagador($client),

            "dataEmissao"       => date('Y-m-d'),
            "seuNumero"         => "1234567810",
            "dataLimite"        => "SESSENTA",
            "dataVencimento"    => date('Y-m-d', strtotime($boletoExpirationDate)),

            "mensagem"          => array(
                "linha1"        => $boletoInstructions
            ),

            "desconto1"         => $discont,
            "desconto2"         => $discont,
            "desconto3"         => $discont,

            "valorNominal"      => self::amountRound($amount),
            "valorAbatimento"   => 0,

            "multa"             => array(
                "codigoMulta"   => "PERCENTUAL",
                "data"          => "2020-08-31",
                "valor"         => 0,
                "taxa"          => 5
            ),
               
            "mora"              => array(
                "codigoMora"    => "TAXAMENSAL",
                "data"          => "2020-08-31",
                "valor"         => 0,
                "taxa"          => 1
            ),
                
            "cnpjCPFBeneficiario"   => "23130935000198",
            "numDiasAgenda"         => "SESSENTA"
        );
        
        $body = json_encode($fields);

        $account_id = Settings::findObjectByKey('banco_inter_account_id');
        $header = [
            'accept: application/json',
            'content-type: application/json',
            'x-inter-conta-corrente: '.$account_id->value,
        ];

        $requestType = self::POST_REQUEST;
        $apiRequest = self::apiRequest($url, $body, $header, $requestType);

        return $apiRequest;
    }

    public static function retrieve($transaction)
    {
        $transactionToken = $transaction->gateway_transaction_id;

        $url = sprintf('%s/%s', self::apiGetUrl(), $transactionToken);

        $body = null;

        $account_id = Settings::findObjectByKey('banco_inter_account_id');
        $header = [
            'accept: application/json',
            'content-type: application/json',
            'x-inter-conta-corrente: '.$account_id->value,
        ];

        $requestType = self::GET_REQUEST;
        $apiRequest = self::apiRequest($url, $body, $header, $requestType);

        return $apiRequest;
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
}
