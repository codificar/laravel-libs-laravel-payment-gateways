<?php

namespace Codificar\PaymentGateways\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

//FormRequest
use Codificar\PaymentGateways\Http\Requests\AddCardTransbankUserFormRequest;
//Resources
use Codificar\PaymentGateways\Http\Resources\AddCardTransbankUserResource;

use Codificar\PaymentGateways\Libs\BancardApi;

use View, User, Input;
use Payment;

class TransbankController extends Controller
{

    /**
     * @api{post}/user/transbank/add_card
     * @apiDescription Adiciona cartão do usuário para a Transbank
     * @return Json
     */
    public function addCard(AddCardTransbankUserFormRequest $request)
    {
        //get transbank iframe
        $gateway = \PaymentFactory::createGateway();
        $link = $gateway->createCard(new Payment, $request->user);

        //validação de sucesso com retorno de iframe
        return new AddCardTransbankUserResource(['link' => $link]);
    }

    /**
     * {get}/transbank/card/{formAction}/{tokenWs}
     * @param $formAction - 
     * @param $tokenWs - 
     * @return View
     */
    public function getCardLink($tbk_token, $url_webpay)
    {
        //seta conf webpay
        $gateway = \PaymentFactory::createGateway();

        //reverte conversao de url
        $url_webpay = $gateway->urlConvert($url_webpay, true);

        //envia post para webpay para gerar form
        return View::make('transbank.card')->with(
            [
                'action' => $url_webpay,
                'token' => $tbk_token
            ]
        );
    }

    /**
     * {post}/transbank/return
     * @return redirect
     */
    public function returnCard(Request $request)
    {
        //seta conf webpay
        \PaymentFactory::createGateway();

        //confirma cadastro
        $response = MallInscription::finish($request->TBK_TOKEN);

        //recupera token card
        $tbkUser = $response->getTbkUser();

        //recupera ultimos digitos do cartão
        $last_four = substr($response->getCardNumber(), -4);

        //recupera e define padrão da bandeira do cartão
        $card_brand = $response->getCardType();
        if (preg_match('/MASTER/', $card_brand)) {
            $card_brand = "master";
        } elseif (preg_match('/VISA/', $card_brand)) {
            $card_brand = "visa";
        } else {
            $card_brand = strtolower(explode(" ", $card_brand)[0]);
        }

        //verifica se cartão foi add
        if ($tbkUser && !$response->responseCode) {

            //recupera mesmo payment
            $payment = Payment::whereCardType($card_brand)->whereCustomerId($response->getTbkUser())->whereLastFour($last_four)->first();

            if (!$payment) { //recupera payment alvo
                $msg = trans('payment.add_card_success');
                $payment = Payment::whereEncrypted($request->TBK_TOKEN)->first();
            } else { //apaga card aux
                $msg = trans('payment.update_card_success');
                Payment::whereEncrypted($request->TBK_TOKEN)->delete();
            }

            //recupera todos cartoes deste user
            $payments_array = Payment::whereUserId($payment->user_id);
            if (!$payments_array->get()) Payment::whereLedgerId($payment->ledger_id);

            //seta cartoes antigos como default false
            $payments_array->update(array('is_default' => false));

            //seta dados do novo cartao e seta como default
            $payment->card_type = $card_brand;
            $payment->customer_id = $tbkUser;
            $payment->last_four = $last_four;
            $payment->is_default = true;
            $payment->is_active = true;
            $payment->save();

            //aviso de sucesso no browser
            echo "<script>alert('" . $msg . "');</script>";
            echo "<script>window.close();</script>";
        } else {

            //deleta payment alvo
            $payment = Payment::whereEncrypted($request->TBK_TOKEN)->delete();

            //aviso de falha no browser
            echo "<script>alert('" . trans('payment.add_card_failed') . "');</script>";
            echo "<script>window.close();</script>";
        }
    }
}
