<?php

namespace Codificar\PaymentGateways\Libs;

use Exception;
use Illuminate\Support\Facades\App;
use Ledger, Input;
use Payment;

/**
 * Class BancardApi
 *
 * @package MotoboyApp
 *
 * @author  Andre Gustavo <andre.gustavo@codificar.com.br>
 */
class BancardApi
{
    public static $APP_API_PROD = "https://vpos.infonet.com.py";
    public static $APP_API_DEV = "https://vpos.infonet.com.py:8888";

    //apis
    const API_ROTA = "vpos/api";
    const API_VERSION = "0.3";
    //curl
    const HEADER = array('Content-Type: application/json');
    const APP_TIMEOUT = 200;
    const CURRENCY = "PYG";
    //status
    const STATUS_SUCCESS = 'success';
    const STATUS_REFUND = 'AlreadyRollbackedError';

    /* Método para recuperar a api conforme ambiente
     * @return string
     */

    public function getApi()
    {
        //se não estiver em ambiente de produção, retorna o dev
        if (App::environment('production')) {
            $api = self::$APP_API_PROD;
        } else {
            $api = self::$APP_API_DEV;
        }
        //retorno
        return $api;
    }

    /* Método para gerar o token da requisição da bancard conforme as variáveis necessárias
     * @return string
     */

    public function generateToken($vars = [])
    {

        //monta chave unindo as variáveis
        $key = "";
        foreach ($vars as $var) {
            $key .= $var;
        }

        //gera token único
        $token = md5($key);

        //retorno
        return $token;
    }

    /* Método para recuperar url conforme endpoint
     * @return string
     */

    public function generateURL($endpoint)
    {

        //monta url
        $url = sprintf('%s/%s/%s/%s', self::getApi(), self::API_ROTA, self::API_VERSION, $endpoint);

        //retorno
        return $url;
    }

    /* Método para solicitar a criação de um cartão na Bancard
     * @param $public_key - chave pública da bancard para solicitação
     * @param $private_key - chave privada da bancard para gerar o token
     * @param $user User - usuário do cartão
     * @return array
     */

    public static function createCard($public_key, $private_key, $user = null, $provider = null)
    {

        try {

            //inicia e gera a url
            $session = curl_init();
            $url = self::generateURL("cards/new");

            //gera um token para o usuário
            $customer_vars = array($user ? $user->id : ($provider ? $provider->id : null), $user ? "customer_id" : ($provider ? "provider_id" : null));
            $customer_id = crc32(self::generateToken($customer_vars));

            //gera um token para o cartão
            $card_vars = array($user ? $user->id : ($provider ? $provider->id : null), rand(), "card_id");
            $card_id = crc32(self::generateToken($card_vars));

            //gera o token da requisição da bancard
            $token_vars = array($private_key, $card_id, $customer_id, "request_new_card");
            $token = self::generateToken($token_vars);

            //monta os campos para solicitação
            $fields = array(
                "public_key" => $public_key,
                "operation" => array(
                    "token" => $token,
                    "card_id" => $card_id,
                    "user_id" => $customer_id,
                    "user_cell_phone" => $user ? $user->phone : ($provider ? $provider->phone : null),
                    "user_mail" => $user ? $user->email : ($provider ? $provider->email : null),
                    "return_url" => \Config::get('app.url') . "/libs/gateways/bancard/return/" . ($user ? $user->id : 0) . "/" . ($provider ? $provider->id : 0)
                )
            );

            curl_setopt($session, CURLOPT_URL, $url);
            curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($session, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($session, CURLOPT_POST, true);
            curl_setopt($session, CURLOPT_POSTFIELDS, json_encode($fields));
            curl_setopt($session, CURLOPT_TIMEOUT, self::APP_TIMEOUT);
            curl_setopt($session, CURLOPT_HTTPHEADER, self::HEADER);

            //solicita
            $msg_chk = curl_exec($session);
            //retorno
            $result = json_decode($msg_chk);

            //em caso de sucesso, chama o iframe para cadastro das informações
            if ($result->status == self::STATUS_SUCCESS) {
                return \Config::get('app.url') . "/libs/gateways/bancard/iframe_card/" . $result->process_id;
            } else {
                return array(
                    'success' => false,
                    'message' => $result->messages
                );
            }
        } catch (Exception $ex) {
            $return = array(
                "success" => false,
                "message" => $ex->getMessage()
            );
            return $return;
        }
    }

    /* Método para recuperar os cartões de um usuário na bancard
     * @param $public_key - chave pública da bancard para solicitação
     * @param $private_key - chave privada da bancard para gerar o token
     * @param $user User - usuário do cartão
     * @return array
     */

    public static function getCards($public_key, $private_key, $user = null, $provider = null)
    {

        try {
            //get token do usuário relacionado ao id
            $customer_vars = array($user ? $user->id : ($provider ?  $provider->id : null),  $user ? "customer_id" : ($provider ? "provider_id" : null));
            $customer_id = crc32(self::generateToken($customer_vars));

            //inicia e gera a url
            $session = curl_init();
            $url = self::generateURL('users/' . $customer_id . '/cards');

            //gera o token da requisição da bancard
            $token_vars = array($private_key, $customer_id, "request_user_cards");
            $token = self::generateToken($token_vars);

            //monta os campos para solicitação
            $fields = array(
                "public_key" => $public_key,
                "operation" => array(
                    "token" => $token
                )
            );

            curl_setopt($session, CURLOPT_URL, $url);
            curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($session, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($session, CURLOPT_POST, true);
            curl_setopt($session, CURLOPT_POSTFIELDS, json_encode($fields));
            curl_setopt($session, CURLOPT_TIMEOUT, self::APP_TIMEOUT);
            curl_setopt($session, CURLOPT_HTTPHEADER, self::HEADER);

            //solicita
            $msg_chk = curl_exec($session);
            //retorno
            $result = json_decode($msg_chk);

            //em caso de sucesso, salva no banco os cartões que não existem
            if ($result->status == self::STATUS_SUCCESS) {

                foreach ($result->cards as $card) {

                    //recupera card antigo
                    $card_old = Payment::whereCardToken($card->card_id)->first();

                    //se não existir o cartão no banco, salva
                    if ($card_old == null) {

                        $card_brand = $card->card_brand;

                        if (preg_match('/MASTER/', $card_brand)) {
                            $card_brand = "master";
                        } elseif (preg_match('/VISA/', $card_brand)) {
                            $card_brand = "visa";
                        } else {
                            $card_brand = strtolower(explode(" ", $card_brand)[0]);
                        }

                        // Do necessary operations
                        if ($user)
                            $payments_array = Payment::whereUserId($user->id);
                        elseif ($provider) {
                            $ledger = Ledger::whereProviderId($provider->id)->first();
                            $payments_array = Payment::whereLedgerId($ledger->id);
                        }

                        if (!$payments_array->get()) Payment::whereLedgerId($user->ledger->id);

                        $payments_array->update(array('is_default' => false));

                        $payment = new Payment();
                        $payment->card_type = $card_brand;
                        $payment->ledger_id = $provider && $ledger ? $ledger->id : null;
                        $payment->user_id = $user ? $user->id : null;
                        $payment->customer_id = $customer_id;
                        $payment->last_four = substr($card->card_masked_number, -4);
                        $payment->is_default = true;
                        $payment->is_active = true;
                        $payment->card_token = $card->card_id;
                        $payment->save();
                    }
                }

                //retorna cartões
                return $result;
            } else {
                return array(
                    'success' => false,
                    'message' => trans("paymentError." . (isset($result->messages[0]->key) ? $result->messages[0]->key : 'general'))
                );
            }
        } catch (Exception $ex) {
            $return = array(
                "success" => false,
                "message" => $ex->getMessage()
            );
            return $return;
        }
    }

    /* Método para realizar cobrança no cartão do comprador sem repassar valor algum ao prestador
     * @param $public_key - chave pública da bancard para solicitação
     * @param $private_key - chave privada da bancard para gerar o token
     * @param $amount - valor a ser capturado
     * @param $description - descrição da compra
     * @param $aliasToken - token do cartão do usuário recuperado na bancard
     * @return array
     */

    public static function pay($public_key, $private_key, $amount, $description, $aliasToken)
    {

        try {

            //inicia e gera a url
            $session = curl_init();
            $url = self::generateURL("charge");

            //get uniqid do shop_process_id relacionado ao aliasToken
            $shop_vars = array($aliasToken, "shop_process_id");
            $shop_process_id = crc32(uniqid(self::generateToken($shop_vars)));

            //gera o token da requisição da bancard
            $vars = array($private_key, $shop_process_id, "charge", $amount, self::CURRENCY, $aliasToken);
            $token = self::generateToken($vars);

            //monta os campos para solicitação
            $fields = array(
                "public_key" => $public_key,
                "operation" => array(
                    "token" => $token,
                    "shop_process_id" => $shop_process_id,
                    "amount" => $amount,
                    "number_of_payments" => 1,
                    "currency" => self::CURRENCY,
                    "additional_data" => "",
                    "description" => $description,
                    "alias_token" => $aliasToken
                )
            );

            curl_setopt($session, CURLOPT_URL, $url);
            curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($session, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($session, CURLOPT_POST, true);
            curl_setopt($session, CURLOPT_POSTFIELDS, json_encode($fields));
            curl_setopt($session, CURLOPT_TIMEOUT, self::APP_TIMEOUT);
            curl_setopt($session, CURLOPT_HTTPHEADER, self::HEADER);

            //solicita
            $msg_chk = curl_exec($session);

            //retorno
            $result = json_decode($msg_chk);

            //verifica se deu erro
            if ($result->status != self::STATUS_SUCCESS) {
                return array(
                    'success' => false,
                    'message' => $result->messages
                );
            }

            //success
            return array(
                'success' => true,
                'paymentId' => $result->confirmation->shop_process_id
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

    /* Método para realizar reembolso da transação
     * @param $public_key - chave pública da bancard para solicitação
     * @param $private_key - chave privada da bancard para gerar o token
     * @param $shop_process_id - id da transação na bancard
     * @return array
     */

    public static function payback($public_key, $private_key, $shop_process_id)
    {

        try {

            //inicia e gera a url
            $session = curl_init();
            $url = self::generateURL('single_buy/rollback');

            //gera o token da requisição da bancard
            $vars = array($private_key, $shop_process_id, "rollback", "0.00");
            $token = self::generateToken($vars);

            //monta os campos para solicitação
            $fields = array(
                "public_key" => $public_key,
                "operation" => array(
                    "token" => $token,
                    "shop_process_id" => $shop_process_id
                )
            );

            curl_setopt($session, CURLOPT_URL, $url);
            curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($session, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($session, CURLOPT_POST, true);
            curl_setopt($session, CURLOPT_POSTFIELDS, json_encode($fields));
            curl_setopt($session, CURLOPT_TIMEOUT, self::APP_TIMEOUT);
            curl_setopt($session, CURLOPT_HTTPHEADER, self::HEADER);

            //solicita
            $msg_chk = curl_exec($session);

            //retorno
            $result = json_decode($msg_chk);

            //verifica se deu erro
            if ($result->status != self::STATUS_SUCCESS) {
                return array(
                    'success' => false,
                    'message' => $result->messages
                );
            }

            //success
            return array(
                'success' => true,
            );
        } catch (Exception $ex) {
            $return = array(
                "success" => false,
                "message" => $ex->getMessage()
            );

            \Log::error(json_encode($return));

            return $return;
        }
    }

    public static function capture($clientId, $token, $userId, $paymentId, $amount)
    {
        //não utilizado para bancard no momento
    }

    /* Método para recuperar transação na Bancard
     * @param $public_key - chave pública da bancard para solicitação
     * @param $private_key - chave privada da bancard para gerar o token
     * @param $shop_process_id - id da transação na bancard
     * @return array
     */

    public static function retrieve($public_key, $private_key, $shop_process_id)
    {

        try {

            //inicia e gera a url
            $session = curl_init();
            $url = self::generateURL('single_buy/confirmations');

            //gera o token da requisição da bancard
            $vars = array($private_key, $shop_process_id, "get_confirmation");
            $token = self::generateToken($vars);

            //monta os campos para solicitação
            $fields = array(
                "public_key" => $public_key,
                "operation" => array(
                    "token" => $token,
                    "shop_process_id" => $shop_process_id
                )
            );

            curl_setopt($session, CURLOPT_URL, $url);
            curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($session, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($session, CURLOPT_POST, true);
            curl_setopt($session, CURLOPT_POSTFIELDS, json_encode($fields));
            curl_setopt($session, CURLOPT_TIMEOUT, self::APP_TIMEOUT);
            curl_setopt($session, CURLOPT_HTTPHEADER, self::HEADER);

            //solicita
            $msg_chk = curl_exec($session);

            //retorna
            $result = json_decode($msg_chk);

            //verifica se deu erro
            if ($result->status != self::STATUS_SUCCESS) {
                return array(
                    'success' => false,
                    'message' => $result->messages
                );
            }

            //success
            return array(
                'success' => true,
                'id' => $result->confirmation->shop_process_id,
                'amount_cents' => $result->confirmation->amount,
                'description' => $result->confirmation->response_details,
                'status' => $result->confirmation->response_description,
                'paymentable_type' => $result->confirmation->security_information,
                'capture' => true
            );
        } catch (Exception $ex) {
            $return = array(
                "success" => false,
                "message" => $ex->getMessage()
            );
            return $return;
        }
    }

    /* Método para deletar cartão na Bancard
     * @param $public_key - chave pública da bancard para solicitação
     * @param $private_key - chave privada da bancard para gerar o token
     * @param $payment - cartão do banco de dados para recuperar customer_id e id
     * @param $aliasToken - token do cartão do usuário recuperado na bancard
     * @return array
     */

    public static function deleteCard($public_key, $private_key, $payment, $aliasToken)
    {

        try {

            //inicia e gera a url
            $session = curl_init();
            $url = self::generateURL('users/' . $payment->customer_id . '/cards');

            //gera o token da requisição da bancard
            $vars = array($private_key, "delete_card", $payment->customer_id, $aliasToken);
            $token = self::generateToken($vars);

            //monta os campos para solicitação
            $fields = array(
                "public_key" => $public_key,
                "operation" => array(
                    "token" => $token,
                    "alias_token" => $aliasToken
                )
            );

            curl_setopt($session, CURLOPT_URL, $url);
            curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($session, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($session, CURLOPT_CUSTOMREQUEST, 'DELETE');
            curl_setopt($session, CURLOPT_POSTFIELDS, json_encode($fields));
            curl_setopt($session, CURLOPT_TIMEOUT, self::APP_TIMEOUT);
            curl_setopt($session, CURLOPT_HTTPHEADER, self::HEADER);

            //solicita
            $msg_chk = curl_exec($session);

            //retorno
            $result = json_decode($msg_chk);

            //verifica se deu erro
            if ($result->status != self::STATUS_SUCCESS) {
                return array(
                    'success' => false,
                    'message' => $result->messages
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
