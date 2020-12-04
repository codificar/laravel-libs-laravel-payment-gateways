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
use Provider, Settings, Ledger, Finance, Bank, LedgerBankAccount;
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
    ];

    public $payment_gateways =  array(
        array('value' => 'pagarme', 'name' => 'setting.pagarme'),
        array('value' => 'stripe', 'name' => 'setting.stripe'),
        array('value' => 'zoop', 'name' => 'setting.zoop'),
        array('value' => 'cielo', 'name' => 'setting.cielo'),
        array('value' => 'braspag', 'name' => 'setting.braspag'),
        array('value' => 'getnet', 'name' => 'setting.getnet'),
        array('value' => 'directpay', 'name' => 'setting.directpay'),
        array('value' => 'bancard', 'name' => 'setting.bancard'),
        array('value' => 'transbank', 'name' => 'setting.transbank'),
        array('value' => 'gerencianet', 'name' => 'setting.gerencianet'),
    );

    /**
     * Recupera settings e acessa view
     * @return View
     */
    public function getSettings()
    {
        // Enums
        $enums = array(
            'default_payment' => '',
            'auto_transfer_provider_payment' => Config::get('enum.auto_transfer_provider_payment'),
            'auto_transfer_schedule_at_after_selected_number_of_days' => Config::get('enum.auto_transfer_schedule_at_after_selected_number_of_days'),
            'stripe_connect' => config('enum.stripe_connect'),
            'stripe_total_split_refund' => config('enum.stripe_total_split_refund')
        );

        //recupera settings
        $settings = [];

        //recupera valores
        foreach ($enums as $key => $conf) {
            $setting = Settings::where('key', '=', $key)->first();
            $settings[$key] = $setting ? $setting->value : null;
        }

        //recupera valores
        foreach ($this->keys_gateways as $key => $values) {
            foreach ($values as $value) {
                $temp_setting = Settings::where('key', '=', $value)->first();
                $settings[$key][$value] = $temp_setting ? $temp_setting->value : null;
            }
        }

        //retorna view
        return View::make('gateways::settings')
            ->with([
                'payment_gateways' => $this->payment_gateways,
                'settings' => $settings,
                'enums' => $enums
            ]);
    }

    /**
     * @api{post}/libs/settings/save/gateways
     * Save payment default and confs
     * @return Json
     */
    public function saveSettings(GatewaysFormRequest $request)
    {
        //recupera e salva payment default
        foreach ($request->settings as $key => $value) {
            $setting = Settings::where('key', '=', $key)->first();
            if ($setting && $value) {
                $setting->value = $value;
                $setting->save();
            }
        }

        //salva confs
        $first_setting = Settings::first();
        foreach ($request->settings[$request->settings['default_payment']] as $key => $value) {
            $temp_setting = Settings::where('key', '=', $key)->first();
            if ($temp_setting && $value) {
                $temp_setting->value = $value;
                $temp_setting->save();
            } else if ($value) {
                $new_setting = new Settings();
                $new_setting->key = $key;
                $new_setting->value = $value;
                $new_setting->page = $first_setting->page;
                $new_setting->category = $first_setting->category;
                $new_setting->save();
            }
        }

        // Return data
        return new GatewaysResource([]);
    }
}