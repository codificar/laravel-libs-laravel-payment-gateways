<?php

/**
 * Class MercadoPagoApi
 *
 * @package MotoboyApp
 *
 * @author  Andre Gustavo <andre.gustavo@codificar.com.br>
 */
class MercadoPagoApi {

    //curl
    const HEADER = array('Content-Type: application/json');
    const APP_TIMEOUT = 200;
    //status
    const MP_APPROVED = 'approved';
    const MP_IN_PROCESS = 'in_process';
    const MP_REJECTED = 'rejected';
    const MP_AUTHORIZED = 'authorized';

    /* Método para realizar cobrança no cartão do comprador sem repassar valor algum ao prestador
     * @param $access_token - chave privada para requisições
     * @param $transaction_id - para capturar
     * @return array
     */

    public static function pay($access_token, $transaction_id) {

        try {

            //inicia e gera a url
            $session = curl_init();

            //monta os campos para solicitação
            $fields = array(
                "capture" => true
            );

            //monta url
            $url = "https://api.mercadopago.com/v1/payments/" . $transaction_id . "?access_token=" . $access_token;

            curl_setopt($session, CURLOPT_URL, $url);
            curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($session, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($session, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($session, CURLOPT_POSTFIELDS, json_encode($fields));
            curl_setopt($session, CURLOPT_TIMEOUT, self::APP_TIMEOUT);
            curl_setopt($session, CURLOPT_HTTPHEADER, self::HEADER);

            //solicita
            $msg_chk = curl_exec($session);

            //retorno
            $result = json_decode($msg_chk);

            //verifica se deu erro
            if ($result->status != self::MP_APPROVED) {
                return array(
                    'success' => false,
                    'status' => $result->status,
                    'message' => $result->error->message
                );
            }

            //success
            return array(
                'success' => true,
                'status' => $result->status,
                'paymentId' => $result->id
            );
        }
        //exceções
        catch (Exception $ex) {
            $return = array(
                "success" => false,
                "message" => $ex->getMessage()
            );

            return $return;
        }
    }

    /* Método para deletar cartão
     * @param $access_token - chave privada para requisição
     * @param $payment - cartão do banco de dados para recuperar customer_id e card_token
     * @return array
     */

    public static function deleteCard($access_token, $payment) {

        try {

            //inicia e gera a url
            $session = curl_init();

            //monta url
            $url = "https://api.mercadopago.com/v1/customers/" .
                    $payment->customer_id . "/cards/" . $payment->card_token . "?access_token=" . $access_token;

            curl_setopt($session, CURLOPT_URL, $url);
            curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($session, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($session, CURLOPT_CUSTOMREQUEST, 'DELETE');
            curl_setopt($session, CURLOPT_TIMEOUT, self::APP_TIMEOUT);
            curl_setopt($session, CURLOPT_HTTPHEADER, self::HEADER);

            //solicita
            $msg_chk = curl_exec($session);

            //retorno
            $result = json_decode($msg_chk);

            //verifica se deu erro
            if (!$result) {
                return array(
                    'success' => false,
                    'message' => ''
                );
            }

            //sucess
            return array(
                'success' => true,
                'card_id' => $payment->id
            );
        } catch (Exception $ex) {
            $return = array(
                "success" => false,
                "message" => $ex->getMessage()
            );

            return $return;
        }
    }

}
