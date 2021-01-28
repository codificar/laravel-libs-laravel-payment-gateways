<?php

namespace Codificar\PaymentGateways\Libs;

use Payment;
use Provider;
use Transaction;
use User;
use LedgerBankAccount;
use Settings;

class CartoApi  
{
    const BALANCE       = 'saldo';
    const SELLER        = 'vendaAVista';
    const REFUND        = 'estornoVenda';
    const EXTRACT       = 'extratoCartao';
    const INSTALLMENT_SALE  =   'vendaparcelada';

    const BALANCEBODY   = 'ns1:Saldo';
    const SELLERBODY    = 'ws:vendaAVista';
    const REFUNDBODY    = 'ns1:EstornoVenda';
    const EXTRACTBODY   = 'ns1:ExtratoCartao';
    const INSTALLMENT_SALE_BODY  =   'ns1:VendaParcelada';


    const CAPTURE_STATUS_SUCCEEDED  =   'Transação de venda à vista realizada com sucesso';
    const REFUND_STATUS_SUCCEEDED   =   'Transação de estorno de venda realizada com sucesso';
    const EXTRACT_STATUS_SUCCEEDED   =   'Transação de estorno de venda realizada com sucesso';
    const BALANCE_STATUS_SUCCEEDED   =   'Transação de verificação de saldo realizada com sucesso';
    const INSTALLMENT_SALE_STATUS_SUCCEEDED   =   'Transação de venda parcelada realizada com sucesso';

    const INSTALLMENTS_QUANTITY = 4;
    const FIRST_VALUE = 0;

    const URL = 'http://www2.carto.com.br/cartows1.2';
    // const URL = 'http://preproducao.carto.com.br:80/cartows1.2/';

    public static function checkCardBalance($payment, $amount, $user)
    {
        $url = sprintf('%s/SaldoWS', self::URL);

        $function = self::BALANCE;

        $bodyFunction = self::BALANCEBODY;

        $response = self::apiRequest($payment, null, $url, $function, $bodyFunction, null);
        $balance = str_replace(['R', '$', '.', ',', ' '], ['', '', '', '.', ''], $response->data->balance );
        $balance = doubleval($balance);



        if ($balance >= $amount) {
            $result = (object)array(
                'success'           =>  $response->success,
                'transaction_id'    =>  $response->data->transaction_id,
                'status'            =>  $response->data->message 
            );
        } else {
            $result = (object)array(
                'success'           =>  false,
                'transaction_id'    =>  null,
                'status'            =>  "Saldo Insuficiente" 
            );
        }

        return $result;

        
    }

    public static function capture($amount, $payment)
    {
        // $url = "http://preproducao.carto.com.br:80/cartows1.2/VendaAVistaWS?wsdl";

        // $url = sprintf('%s/VendaAVistaWS?wsd', self::URL);

        $url = sprintf('%s/VendaParceladaWS?wsd', self::URL);

        $function = self::INSTALLMENT_SALE;
        
        $bodyFunction = self::INSTALLMENT_SALE_BODY;

        $response = self::apiRequest($payment, $amount, $url, $function, $bodyFunction, $transaction = null);
        
        $result = (object)array(
            'success'           =>  $response->success,
            'transaction_id'    =>  $response->data->transaction_id
        );

        return $result;
        
    }

    public static function refund($transaction, $payment)
    {
        // $url = "http://preproducao.carto.com.br:80/cartows1.2/EstornoVendaWS?wsdl";

        $url = sprintf('%s/EstornoVendaParceladaWS?wsdl', self::URL);

        $function = self::REFUND;

        $bodyFunction = self::REFUNDBODY;

        $amount = $transaction->gross_value;

        $response = self::apiRequest($payment, $amount, $url, $function, $bodyFunction, $transaction);
        
        $result = (object)array(
            'success'           =>  $response->success,
            'transaction_id'    =>  $response->data->transaction_id 
        );

        return $result;
    }

    public static function retrive($transaction, $payment)
    {
        $url = "http://preproducao.carto.com.br:80/cartows1.2/EstornoVendaWS?wsdl";

        $function = self::EXTRAC;

        $bodyFunction = self::EXTRACTBODY;

        $response = self::apiRequest($payment, null, $url, $function, $bodyFunction, $transaction);
        
        $result = (object)array(
            'success'           =>  true,
            'transaction_id'    =>  $response->Transacao->idMovimento,
            'status'            =>  $response->Transacao->mensagem 
        );

        return $result;
    }

    private static function getBodyFunction($payment, $amount, $bodyFunction)
    {
        $cardNumber = $payment->getCardNumber();
        $cardPassword = $payment->getPassword();
        // $cardNumber = '1010420013471920';1007  2800  1259  0261
        $cardNumber = '1007280012590261';
        $cardPassword = '123456';
        $cartoLogin = Settings::getCartoLogin();
        $cartoPassword = Settings::getCartoPassword();
        if($amount){
            $value = number_format($amount, 2, ',', '');
        }
        switch ($bodyFunction) {
            case self::BALANCEBODY:
                
                $arguments = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ws="http://ws.connectsa.com.br/" xmlns:sal="http://saldo.ws.connectsa.com.br/">
                <soapenv:Header/>
                <soapenv:Body>
                   <ws:Saldo>
                      <sal:login>'.$cartoLogin.'</sal:login>
                      <sal:senha>'.$cartoPassword.'</sal:senha>
                      <sal:numeroCartao>'.$cardNumber.'</sal:numeroCartao>
                      <sal:senhaCartao>'.$cardPassword.'</sal:senhaCartao>
                      <sal:token></sal:token>
                   </ws:Saldo>
                </soapenv:Body>
             </soapenv:Envelope>';

                return $arguments;
                break;
            
            case self::SELLERBODY:

                $arguments    =   
                '<?xml version="1.0" encoding="UTF-8"?>
                <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ws="http://ws.connectsa.com.br/" xmlns:ven="http://vendaavista.ws.connectsa.com.br/">
                  <soapenv:Body>
                    <ws:VendaAVista>
                      <ven:login>'.$cartoLogin.'</ven:login>
                      <ven:senha>'.$cartoPassword.'</ven:senha>
                      <ven:numeroCartao>'.$cardNumber.'</ven:numeroCartao>
                      <ven:senhaCartao>'.$cardPassword.'</ven:senhaCartao>
                      <ven:valorTransacao>'.$value.'</ven:valorTransacao>
                      <ven:token></ven:token>
                    </ws:VendaAVista>
                  </soapenv:Body>
                </soapenv:Envelope>';

                return $arguments;
                break;
            case self::INSTALLMENT_SALE_BODY:

                $arguments = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ws="http://ws.connectsa.com.br/" xmlns:ven="http://vendaparcelada.ws.connectsa.com.br/">
                <soapenv:Header/>
                <soapenv:Body>
                   <ws:VendaParcelada>
                      <ven:login>'.$cartoLogin.'</ven:login>
                      <ven:senha>'.$cartoPassword.'</ven:senha>
                      <ven:numeroCartao>'.$cardNumber.'</ven:numeroCartao>
                      <ven:senhaCartao>'.$cardPassword.'</ven:senhaCartao>
                      <ven:valorTransacao>'.$value.'</ven:valorTransacao>
                      <ven:valorEntrada>'.self::FIRST_VALUE.'</ven:valorEntrada>
                      <ven:quantidadeParcelas>'.self::INSTALLMENTS_QUANTITY.'</ven:quantidadeParcelas>
                   </ws:VendaParcelada>
                </soapenv:Body>
             </soapenv:Envelope>';

             return $arguments;
                # code...
                break;
            case self::REFUNDBODY:

                $arguments = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ws="http://ws.connectsa.com.br/" xmlns:est="http://estornoVendaParcelada.ws.connectsa.com.br/">
                <soapenv:Header/>
                <soapenv:Body>
                   <ws:EstornoVenda>
                      <est:login>'.$cartoLogin.'</est:login>
                      <est:senha>'.$cartoPassword.'</est:senha>
                      <est:numeroCartao>'.$cardNumber.'</est:numeroCartao>
                      <est:senhaCartao>'.$cardPassword.'</est:senhaCartao>
                      <est:valorTransacao>'.$transaction->gross_value.'</est:valorTransacao>
                      <est:idMovimento>'.$transaction->gateway_transaction_id.'</est:idMovimento>
                   </ws:EstornoVenda>
                </soapenv:Body>
              </soapenv:Envelope>';

                return $arguments;
                break;
            case self::EXTRACTBODY:
                $initialDate = \Carbon\Carbon::parse($suaData)->format('d/m/Y');
                $finalDate = 
                $arguments = array(
                    $bodyFunction =>  array(
                        'login'             =>  $cartoLogin,
                        'senha'             =>  $cartoPassword,
                        'numeroCartao'      =>  $cardNumber,
                        'dataInicial'       =>  $transaction->created_at,
                        'dataFinal'         =>  $transaction->gross_value
                    )
                );

                return $arguments;
                break;
            default:
                # code...
                break;
        }
    }

    private static function apiRequest($payment, $amount = null, $url, $function, $bodyFunction, $transaction)
    {
        $arguments = self::getBodyFunction($payment, $amount, $bodyFunction);
        try {
            $response = self::curlApiRequest($url, $arguments, $bodyFunction);
            if ($response->success) {
                return $response;
            }
        } catch (\Throwable $ex) {
            $return = (object)array(
                "success" 					=> false ,
                "message" 					=> $ex->getMessage()
            );
            
            \Log::error(($return));

            return $return;
        }

    }

    public static function getCurlResponse($msg_chk, $bodyFunction)
    {
        switch ($bodyFunction) {
                case self::BALANCEBODY:
                    $dom = new DOMDocument;
                    $dom->loadXML($msg_chk);
                    
                    $transactionId = ($dom->getElementsByTagName('idMovimento')->item(0)->nodeValue);
                    $message = ($dom->getElementsByTagName('mensagem')->item(0)->nodeValue);
                    $balance = ($dom->getElementsByTagName('valorLimiteCredito')->item(0)->nodeValue);
                    
                    $result = (object)array(
                        'success'           =>  true,
                        'message'           =>  $message,
                        'transaction_id'    =>  $transactionId,
                        'balance'           =>  $balance
                    );

                    break;
                
                case self::SELLERBODY || INSTALLMENT_SALE_BODY:
                    $dom = new DOMDocument;
                    $dom->loadXML($msg_chk);
                    
                    $transactionId = ($dom->getElementsByTagName('idMovimento')->item(0)->nodeValue);
                    $message = ($dom->getElementsByTagName('mensagem')->item(0)->nodeValue);
                    
                    $result = (object)array(
                        'success'           =>  true,
                        'message'           =>  $message,
                        'transaction_id'    =>  $transactionId
                    );

                break;
                case self::REFUNDBODY:
                    $dom = new DOMDocument;
                    $dom->loadXML($msg_chk);
                    
                    $transactionId = ($dom->getElementsByTagName('idMovimento')->item(0)->nodeValue);
                    $message = ($dom->getElementsByTagName('mensagem')->item(0)->nodeValue);
                    
                    $result = (object)array(
                        'success'           =>  true,
                        'message'           =>  $message,
                        'transaction_id'    =>  $transactionId
                    );
                    
                    break;
                case self::EXTRACTBODY:
                    $initialDate = \Carbon\Carbon::parse($suaData)->format('d/m/Y');
                    $finalDate = 
                    $arguments = array(
                        $bodyFunction =>  array(
                            'login'             =>  $cartoLogin,
                            'senha'             =>  $cartoPassword,
                            'numeroCartao'      =>  $cardNumber,
                            'dataInicial'       =>  $transaction->created_at,
                            'dataFinal'         =>  $transaction->gross_value
                        )
                    );
                    break;
                default:
                    # code...
                    break;
            }

            return $result;
    }

    public static function getResponseMessage($bodyFunction)
    {
        switch ($bodyFunction) {
            case self::BALANCEBODY:
                return self::BALANCE_STATUS_SUCCEEDED;
                break;

            case self::SELLERBODY:
                return self::CAPTURE_STATUS_SUCCEEDED;
            break;

            case self::REFUNDBODY:
                return self::REFUND_STATUS_SUCCEEDED;
            break;

            case self::EXTRACTBODY:
                return self::EXTRACT_STATUS_SUCCEEDED;
            break;
            
            default:
                # code...
                break;
        }
    }

    public static function curlApiRequest($url, $arguments, $bodyFunction)
    {
        try
        {
            $session = curl_init();

            $header = array(
                "Content-type: text/xml;charset=\"utf-8\"",
                "Accept: text/xml",
                "Cache-Control: no-cache",
                "Pragma: no-cache",
                "Content-length: ".strlen($arguments),
              );

            curl_setopt($session, CURLOPT_URL, $url);
            curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($session, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($session, CURLOPT_POST, true);
            
            curl_setopt($session, CURLOPT_POSTFIELDS, ($arguments));
            	
            curl_setopt($session, CURLOPT_TIMEOUT, 60);
            curl_setopt($session, CURLOPT_HTTPHEADER, $header);            

            $msg_chk = curl_exec($session);  
            
            \Log::debug('[capture]Response: '.json_encode($msg_chk));
            \Log::debug('[capture]fields: '.($arguments));
            $httpcode = curl_getinfo($session, CURLINFO_HTTP_CODE);  

            \Log::debug('[capture]StatusCode: '.json_encode($httpcode));
            
            $result = self::getCurlResponse($msg_chk, $bodyFunction);
            
            if($httpcode != 200 && $result->responseCode != self::getResponseMessage($bodyFunction))
                throw new Exception('createCard: '. $result->message);

            return (object)array (
                    'success'       =>  true,
                    'data'          =>  $result
                );

        }
        catch(Exception  $ex)
        {
            $return = (object)array(
                "success" 					=> false ,
                "message" 					=> $ex->getMessage()
            );
            
            \Log::error(($return));

            return $return;
        }
    }
    
}