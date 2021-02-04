<?php

namespace Codificar\PaymentGateways\Http\Controllers;

use App\Http\Controllers\Controller;
use Codificar\PaymentGateways\Http\Requests\GatewaysFormRequest;
use Codificar\PaymentGateways\Http\Resources\GatewaysResource;
use Illuminate\Http\Request;

// Importar Resource
use Codificar\PaymentGateways\Http\Resources\TesteResource;
use Config;
use Exception;
use Input, Validator, View, Response;
use Provider, Settings, Ledger, Finance, Bank, LedgerBankAccount, Payment;
use stdClass;

class GatewaysController extends Controller
{
    public $keys_gateways = [
        'pagarme' => [
            'pagarme_encryption_key',
            'pagarme_recipient_id',
            'pagarme_api_key'
        ],
        'stripe' => [
            'stripe_secret_key',
            'stripe_publishable_key',
            'stripe_connect',
            'stripe_total_split_refund'
        ],
        'zoop' => [
            'zoop_marketplace_id',
            'zoop_publishable_key',
            'zoop_seller_id'
        ],
        'cielo' => [
            'cielo_merchant_id',
            'cielo_merchant_key'
        ],
        'braspag' => [
            'braspag_merchant_id',
            'braspag_merchant_key',
            'braspag_token'
        ],
        'getnet' => [
            'getnet_client_id',
            'getnet_client_secret',
            'getnet_seller_id'
        ],
        'directpay' => [
            'directpay_encrypt_key',
            'directpay_encrypt_value',
            'directpay_requester_id',
            'directpay_requester_password',
            'directpay_requester_token',
            'directpay_unique_trx_id'
        ],
        'bancard' => [
            'bancard_public_key',
            'bancard_private_key'
        ],
        'transbank' => [
            'transbank_private_key',
            'transbank_commerce_code',
            'transbank_public_cert'
        ],
        'gerencianet' => [
            'gerencianet_sandbox',
            'gerencianet_client_id',
            'gerencianet_client_secret',

        ],
        'braspag_cielo_ecommerce' => [
            'braspag_client_id',
            'braspag_client_secret'

        ],
    ];

    public $payment_gateways =  array(
        array('value' => 'pagarme', 'name' => 'setting.pagarme'),
        array('value' => 'stripe', 'name' => 'setting.stripe'),
        array('value' => 'zoop', 'name' => 'setting.zoop'),
        array('value' => 'cielo', 'name' => 'setting.cielo'),
        array('value' => 'braspag', 'name' => 'setting.braspag'),
        array('value' => 'braspag_cielo_ecommerce', 'name' => 'setting.braspag_cielo_ecommerce'),
        array('value' => 'getnet', 'name' => 'setting.getnet'),
        array('value' => 'directpay', 'name' => 'setting.directpay'),
        array('value' => 'bancard', 'name' => 'setting.bancard'),
        array('value' => 'transbank', 'name' => 'setting.transbank')
    );


    public $keys_payment_methods =  array(
        'payment_money',
        'payment_card',
        'payment_machine',
        'payment_carto',
        'payment_crypt',
        'payment_debitCard',
        'payment_balance',
        'payment_prepaid',
        'payment_billing'
    );

    /**
     * Recupera settings e acessa view
     * @return View
     */
    public function getSettings()
    {

        //pega os metodos de pagamentos
        $payment_methods = array();
        foreach ($this->keys_payment_methods as $key) {
            $payment_methods[$key] = (bool)Settings::findByKey($key);
        }   

        //configuracoes dos gateways de cartao de credito
        $gateways = array();
        $gateways['default_payment'] = Settings::findByKey('default_payment');
        $gateways['default_payment_boleto'] = Settings::findByKey('default_payment_boleto');
        $gateways['list_gateways'] = $this->payment_gateways;

        //recupera as chaves de todos os gateways
        foreach ($this->keys_gateways as $key => $values) {
            foreach ($values as $value) {
                $temp_setting = Settings::where('key', '=', $value)->first();
                $gateways[$key][$value] = $temp_setting ? $temp_setting->value : null;
            }
        }        
        
        //retorna view
        return View::make('gateways::settings')
            ->with([
                'payment_methods' => $payment_methods,
                'gateways' => $gateways
            ]);
    }

    /**
     * @api{post}/libs/settings/save/gateways
     * Save payment default and confs
     * @return Json
     */

    public function saveSettings(GatewaysFormRequest $request)
    {

        //Salva as formas de pagamento escolhidas
        foreach ($request->payment_methods as $key => $value) {
            //Verifica se a key do gateway existe
            if(in_array($key, $this->keys_payment_methods)) {
                $this->updateOrCreateSettingKey($key, $value);
            }
        }

        //Salva o gateway de cartao de credito escolhido
        $defaultPaymentCard = $request->gateways['default_payment'];
        $this->updateOrCreateSettingKey('default_payment', $defaultPaymentCard);

        //salva as chaves do gateway escolhido
        if($defaultPaymentCard) {
            foreach ($request->gateways[$defaultPaymentCard] as $key => $value) {
                //Verifica se a key do gateway existe
                if(in_array($key, $this->keys_gateways[$defaultPaymentCard])) {
                    $this->updateOrCreateSettingKey($key, $value);
                }
            }
        }
       

        //Salva o gateway de boleto do faturamento
        $defaultPaymentBillet = $request->gateways['default_payment_boleto'];
        $this->updateOrCreateSettingKey('default_payment_boleto', $defaultPaymentBillet);
        
        //Salva as chaves do gateway de boleto do pagamento por faturamento
        if($defaultPaymentBillet) {
            foreach ($request->gateways[$defaultPaymentBillet] as $key => $value) {
                //Verifica se a key do gateway existe
                if(in_array($key, $this->keys_gateways[$defaultPaymentBillet])) {
                    $this->updateOrCreateSettingKey($key, $value);
                }
            }
        }

        // Return data
        return new GatewaysResource([]);
    }

    private function updateOrCreateSettingKey($key, $value) {
        $temp_setting = Settings::where('key', '=', $key)->first();
        if ($temp_setting) {
            $temp_setting->value = $value ? $value : '';
            $temp_setting->save();
        } else {
            $first_setting = Settings::first();
            $new_setting = new Settings();
            $new_setting->key = $key;
            $new_setting->value = $value ? $value : '';
            $new_setting->page = $first_setting->page;
            $new_setting->category = $first_setting->category;
            $new_setting->save();
        }
    }
}
