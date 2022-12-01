<?php

namespace Codificar\PaymentGateways\Http\Controllers;

use App\Http\Controllers\Controller;
use Codificar\PaymentGateways\Http\Requests\GatewaysFormRequest;
use Codificar\PaymentGateways\Http\Resources\GatewaysResource;
use Illuminate\Http\Request;

use Codificar\PaymentGateways\Models\GatewaysLibModel;

// Importar Resource
use Codificar\PaymentGateways\Commands\GatewayUpdateDependenciesJob;
use Codificar\PaymentGateways\Http\Resources\WebhookResource;
use Config;
use Exception;
use Input, Validator, View, Response;
use Provider, Settings, Ledger, Finance, Bank, LedgerBankAccount, Payment, PaymentFactory;
use stdClass;
use Storage;

class GatewaysController extends Controller
{
    const WEEK_DAYS = array(
        array('value' => '1', 'name' => 'setting.sunday')
       ,array('value' => '2', 'name' => 'setting.monday')
       ,array('value' => '3', 'name' => 'setting.tuesday')
       ,array('value' => '4', 'name' => 'setting.wednesday')
       ,array('value' => '5', 'name' => 'setting.thursday')
       ,array('value' => '6', 'name' => 'setting.friday')
       ,array('value' => '7', 'name' => 'setting.saturday')
    );
    
    //credit card gateways
    public $keys_gateways = [
        'pagarme' => [
            'pagarme_secret_key',
            'pagarme_recipient_id',
            'pagarme_token',
            'gateway_product_title'
        ],
        'pagarmev2' => [
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
        'adiq' => [
            'adiq_client_id',
            'adiq_client_secret',
            'adiq_token'
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
        'pagarapido' => [
            'pagarapido_login',
            'pagarapido_password',
            'pagarapido_gateway_key',
            'pagarapido_production'
        ],
        'bancointer' => [
            'banco_inter_account',
            'cnpj_for_banco_inter'
        ],
        'ipag' => [
            'ipag_api_id',
            'ipag_api_key',
            'ipag_token',
            'ipag_antifraud',
            'gateway_product_title',
            'billet_gateway_provider'
        ],
        'juno' => [
            'juno_client_id',
            'juno_secret',
            'juno_resource_token',
            'juno_public_token',
            'juno_auth_token',
            'juno_auth_token_expiration_date',
            'juno_sandbox'
        ],
    ];

    //pix gateways
    public $keys_pix_gateways = [
        'juno' => [
            'pix_juno_client_id',
            'pix_juno_secret',
            'pix_juno_resource_token',
            'pix_juno_public_token',
            'pix_juno_auth_token',
            'pix_juno_auth_token_expiration_date',
            'pix_juno_sandbox'
        ] ,
        'ipag' => [
            'pix_ipag_api_id',
            'pix_ipag_api_key',
            'pix_ipag_expiration_time',
            'pix_ipag_version'

        ]
    ];

    public $payment_gateways =  array(
        array('value' => 'pagarme', 'name' => 'setting.pagarme'),
        array('value' => 'pagarmev2', 'name' => 'setting.pagarmev2'),
        array('value' => 'stripe', 'name' => 'setting.stripe'),
        array('value' => 'zoop', 'name' => 'setting.zoop'),
        array('value' => 'cielo', 'name' => 'setting.cielo'),
        array('value' => 'braspag', 'name' => 'setting.braspag'),
        array('value' => 'braspag_cielo_ecommerce', 'name' => 'setting.braspag_cielo_ecommerce'),
        array('value' => 'getnet', 'name' => 'setting.getnet'),
        array('value' => 'directpay', 'name' => 'setting.directpay'),
        array('value' => 'bancard', 'name' => 'setting.bancard'),
        array('value' => 'transbank', 'name' => 'setting.transbank'),
        array('value' => 'pagarapido', 'name' => 'setting.pagarapido'),
        array('value' => 'adiq', 'name' => 'setting.adiq'),
        array('value' => 'ipag', 'name' => 'setting.ipag'),
        array('value' => 'juno', 'name' => 'setting.juno'),
    );

    public $type_billets =  array(
        array('value' => 'boletozoop', 'name' => 'setting.zoop'),
        array('value' => 'boletopagseguro', 'name' => 'setting.pagseguro'),
        array('value' => 'boletosicoob', 'name' => 'setting.sicoob'),
        array('value' => 'boletosicredi', 'name' => 'setting.sicredi'),
        array('value' => 'boleto_banespasantander', 'name' => 'setting.santander'),
        array('value' => 'boletobb', 'name' => 'setting.bancoDoBrasil'),
        array('value' => 'boletoitaushopline', 'name' => 'setting.itau'),
        array('value' => 'boletoshopfacil', 'name' => 'setting.bradesco'),
        array('value' => 'boletobradesconet', 'name' => 'setting.bradescoNet'),
    );

    public $payment_pix_gateways =  array(
        //array('value' => 'zoop', 'name' => 'setting.zoop'),
        array('value' => 'ipag', 'name' => 'setting.ipag'),
        array('value' => 'juno', 'name' => 'setting.juno'),
    );


    public $keys_payment_methods =  GatewaysLibModel::keysPaymentMethods;

    //Custom names of payment methods
    public $name_keys_payment_methods =  GatewaysLibModel::nameKeysPaymentMethods;

    public $keys_prepaid =  array(
        'prepaid_min_billet_value',
        'prepaid_tax_billet',
        'prepaid_billet_user',
        'prepaid_billet_provider',
        'prepaid_billet_corp',
        'prepaid_card_user',
        'prepaid_card_provider',
        'prepaid_card_corp',
        'prepaid_pix_user',
        'prepaid_pix_provider',
        'prepaid_pix_corp'
    );

    public $keys_settings = array(
        'earnings_report_weekday',
        'show_user_account_statement'
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
        $gateways['compensate_provider_days'] = Settings::findByKey('compensate_provider_days');
        $gateways['list_gateways'] = $this->payment_gateways;
        $gateways['billets'] = $this->type_billets;

        //recupera as chaves de todos os gateways
        foreach ($this->keys_gateways as $key => $values) {
            foreach ($values as $value) {
                $temp_setting = Settings::where('key', '=', $value)->first();
                $gateways[$key][$value] = $temp_setting ? $temp_setting->value : null;
            }
        }   

        //configuracoes dos gateways pix
        $pix_gateways = array();
        $pix_gateways['list_gateways'] = $this->payment_pix_gateways;
        $pix_gateways['default_payment_pix'] = Settings::findByKey('default_payment_pix');
        $pix_gateways['pix_key'] = Settings::findByKey('pix_key');
        $pix_gateways['billet_gateway_provider'] = Settings::findByKey('billet_gateway_provider');
        //recupera as chaves de todos os gateways de pix
        foreach ($this->keys_pix_gateways as $key => $values) {
            foreach ($values as $value) {
                $temp_setting = Settings::where('key', '=', $value)->first();
                $pix_gateways[$key][$value] = $temp_setting ? $temp_setting->value : null;
            }
        }   
        
        //Pega informacoes do carto
        $carto = array();
        $carto['carto_login'] = Settings::findByKey('carto_login');
        $carto['carto_password'] = Settings::findByKey('carto_password');

        //Pega informacoes do bancryp
        $bancryp = array();
        $bancryp['bancryp_api_key'] = Settings::findByKey('bancryp_api_key');
        $bancryp['bancryp_secret_key'] = Settings::findByKey('bancryp_secret_key');

        //Pega informacoes do pre-pago
        $prepaid = array();
        foreach ($this->keys_prepaid as $key) {
            if($key == 'prepaid_min_billet_value' || $key == 'prepaid_tax_billet') {
                $prepaid[$key] = Settings::findByKey($key);
            } else {
                $prepaid[$key] = (bool)Settings::findByKey($key);
            }
        }   
        $settings = array();
        $earnings_report_weekday = Settings::findByKey('earnings_report_weekday');
        $settings['earnings_report_weekday'] = $earnings_report_weekday ? $earnings_report_weekday : '1'; //default 1 = sunday 

        $settings['show_user_account_statement'] = Settings::findByKey('show_user_account_statement');
        
        $settings['enum'] = array(
            'week_days' => self::WEEK_DAYS
        );

        $certificates = array(
            'crt'   => Storage::exists('certificates/BancoInterCertificate.pem'),
            'key'   => Storage::exists('certificates/BancoInterKey.pem')
        );

        //pega as nomenclaturas das formas de pagamento
        $nomenclatures = array();
        foreach ($this->name_keys_payment_methods as $key) {
            $nomenclatures[$key] = Settings::findByKey($key);
        }
        $nomenclatures['payments_custom_name'] = Settings::findByKey('payments_custom_name');

        //retorna view
        return View::make('gateways::settings')
            ->with([
                'payment_methods' => $payment_methods,
                'gateways' => $gateways,
                'pix_gateways' => $pix_gateways,
                'carto' => $carto,
                'bancryp' => $bancryp,
                'prepaid' => $prepaid,
                'settings' => $settings,
                'certificates' => $certificates,
                'nomenclatures' => $nomenclatures,
                'enviroment' => \App::environment()
            ]);
    }

    /**
     * @api{post}/libs/settings/save/gateways
     * Save payment default and confs
     * @return Json
     */

    public function saveSettings(GatewaysFormRequest $request)
    {

        //Salva as configuracoes gerais
        if($request->settings) {
            foreach ($request->settings as $key => $value) {
                //Verifica se a key existe
                if(in_array($key, $this->keys_settings)) {
                    $this->updateOrCreateSettingKey($key, $value);
                }
            }
        }
        
        //Salva as formas de pagamento escolhidas
        foreach ($request->payment_methods as $key => $value) {
            //Verifica se a key do gateway existe
            if(in_array($key, $this->keys_payment_methods)) {
                $this->updateOrCreateSettingKey($key, (bool)$value ? '1' : '0');
            }
        }
        
        //pega o gateway antigo (antes de salvar)
        $oldGateway = Settings::findByKey('default_payment');
        
        //Pega o novo gateway escolhido
        $newGateway = $request->gateways['default_payment'];
        
        $isUpdatingCards = false;
        $estimateUpdateCards = "";
        //Se o gateway antigo for diferante do atual, entao chama a seed para atualizar todos os cartoes
        
        if($oldGateway != $newGateway) {
            //Salva o gateway de cartao de credito escolhido no banco
            $this->updateOrCreateSettingKey('default_payment', $newGateway);

            $isUpdatingCards = true;
            $estimateUpdateCards = GatewaysLibModel::getUpdateCardsEstimateTime();

            //Call the job to update the cards
            GatewayUpdateDependenciesJob::dispatch();
        }

        //Salva o gateway de cartao de credito escolhido
        $this->updateOrCreateSettingKey('default_payment', $newGateway);

        //Salva o gateway de pix escolhido
        $pixGateway = $request->pix_gateways['default_payment_pix'];
        $this->updateOrCreateSettingKey('default_payment_pix', $pixGateway);
        $this->updateOrCreateSettingKey('pix_key', $request->pix_gateways['pix_key']);

        //salva os dias de compensacao futura
        if(isset($request->gateways['compensate_provider_days']) ) {
            $this->updateOrCreateSettingKey('compensate_provider_days', $request->gateways['compensate_provider_days']);
        }
       
        //salva as chaves do gateway de cartao escolhido
        if($newGateway) {
            foreach ($request->gateways[$newGateway] as $key => $value) {
                //Verifica se a key do gateway existe
                if(in_array($key, $this->keys_gateways[$newGateway])) {
                    $this->updateOrCreateSettingKey($key, $value);
                }
            }
        }

        //salva as chaves do gateway de pix escolhido
        if($pixGateway) {
            foreach ($request->pix_gateways[$pixGateway] as $key => $value) {
                //Verifica se a key do gateway existe
                if(in_array($key, $this->keys_pix_gateways[$pixGateway])) {
                    $this->updateOrCreateSettingKey($key, $value);
                }
            }
        }

        //se o gateway for juno, entao chama o metodo para configurar o webhooks
        if(isset($request->payment_methods['payment_gateway_pix']) && 
            $request->payment_methods['payment_gateway_pix'] && 
            ($pixGateway == 'juno' || $pixGateway == 'ipag') 
        ) {
            try {
                $gateway = PaymentFactory::createPixGateway();
                $gateway->createPixWebhooks();
            } catch (Exception $th) {
                \Log::error($th->getMessage());
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

        //Salva as chaves do carto
        if($request->payment_methods['payment_carto']) {
            $this->updateOrCreateSettingKey('carto_login', $request->carto['carto_login']);
            $this->updateOrCreateSettingKey('carto_password', $request->carto['carto_password']);
        }

        //Salva as chaves do bancryp
        if($request->payment_methods['payment_crypt']) {
            $this->updateOrCreateSettingKey('bancryp_api_key', $request->bancryp['bancryp_api_key']);
            $this->updateOrCreateSettingKey('bancryp_secret_key', $request->bancryp['bancryp_secret_key']);
        }

        //Salva as configuracoes do pre-pago
        if($request->payment_methods['payment_prepaid']) {
            foreach ($request->prepaid as $key => $value) {
                //Verifica se a key existe
                if(in_array($key, $this->keys_prepaid)) {
                    $this->updateOrCreateSettingKey($key, $value);
                }
            }
        }

        //salva as nomenclatuas
        foreach ($request->nomenclatures as $key => $value) {
            //Verifica se a key existe
            if(in_array($key, $this->name_keys_payment_methods) || $key == 'payments_custom_name') {
                $this->updateOrCreateSettingKey($key, $value);
            }
        }

        // Return data
        return new GatewaysResource([
            'is_updating_cards' => $isUpdatingCards,
            'estimate_update_cards' => $estimateUpdateCards
        ]);
    }

    /**
     * @api{post}/libs/settings/retrieve/webhooks
     * retrieve Webhooks pix
     * @return Json
     */
    public function retrieveWebhooks()
    {
        $success = false;
        $message = '';
        $webhooks = [];

        //verificar se o pix gateway está ativo
        $isGatewayPix = Settings::getPaymentGatewayPix();
        if($isGatewayPix == 1) {
            $defaultGatewaPix = Settings::getDefaultPaymentPix();
            if(isset($defaultGatewaPix) && !empty($defaultGatewaPix) && $defaultGatewaPix == 'ipag') {
                try {
                    $gateway = PaymentFactory::createPixGateway();
                    $webhooks = $gateway->retrieveWebhooks(true);
                    $success = true;
                    $message = 'Webhooks recuperados com sucesso';
                } catch (Exception $th) {
                    \Log::error($th->getMessage());
                    $message = 'Erro ao recuperar webhooks: ' . $th->getMessage();
                }
            } else {
                $message = 'Para recuperar webhooks, é necessário ter o gateway ipag ativo';
            }
        } else {
            $message = 'Não foi possível recuperar webhooks, pois o gateway de pagamento não está ativo';
        }

        // Return data
        return new webhookResource([
            'success' => $success,
            'data' => $webhooks,
            'message' => $message
        ]);
    }

    public function getNomenclatures() {

        $nomenclatures = array();
        $payment_nomenclature = "";
        if((bool)Settings::findByKey('payments_custom_name')) {
            foreach ($this->name_keys_payment_methods as $key) {

                if(Settings::findByKey($key) == ''){
                    
                    $payment_nomenclature = $this->paymentNomenclature($key);
                }
                $nomenclatures[$key] = Settings::findByKey($key) != "" ? Settings::findByKey($key) : $payment_nomenclature;
            }
        } else {
            foreach ($this->name_keys_payment_methods as $key) {
                $nomenclatures[$key] = $this->paymentNomenclature($key);
            }
        }

        return response()->json($nomenclatures);

    }

    private function paymentNomenclature($key){

        $payment_nomenclature = trans('payment.'.$key);
        return $payment_nomenclature;
    }

    private function updateOrCreateSettingKey($key, $value) {
        $temp_setting = Settings::where('key', '=', $key)->first();

        if($value === 0 || $value === '0' || $value === false ) {
            $newValue = '0';
        }
        else if($value) {
            $newValue = $value;
        } else {
            $newValue = '';
        }

        if ($temp_setting) {
            $temp_setting->value = $newValue;
            $temp_setting->save();
        } else {
            $first_setting = Settings::first();
            $new_setting = new Settings();
            $new_setting->key = $key;
            $new_setting->value = $newValue;
            $new_setting->page = $first_setting->page;
            $new_setting->category = $first_setting->category;
            $new_setting->save();
        }
    }

    
}
