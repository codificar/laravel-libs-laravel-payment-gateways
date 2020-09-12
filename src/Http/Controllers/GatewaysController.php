<?php

namespace Codificar\PaymentGateways\Http\Controllers;

use App\Http\Controllers\Controller;
use Codificar\PaymentGateways\Http\Requests\GatewaysFormRequest;
use Codificar\PaymentGateways\Http\Resources\GatewaysResource;
use Illuminate\Http\Request;

// Importar models
use Codificar\PaymentGateways\Models\Generic;

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
            'stripe_secret',
            'stripe_publishable_key'
        ],
        'zoop' => [
            'zoop_marketplace_id',
            'zoop_publishable_key',
            'zoop_seller_id'
        ]
    ];

    public $payment_gateways =  array(
        array('value' => 'pagarme', 'name' => 'setting.pagarme'),
        array('value' => 'byebnk', 'name' => 'setting.byebnk'),
        array('value' => 'stripe', 'name' => 'setting.stripe'),
        array('value' => 'braintree', 'name' => 'setting.braintree'),
        array('value' => 'zoop', 'name' => 'setting.zoop'),
        array('value' => 'bancard', 'name' => 'setting.bancard'),
        array('value' => 'mercadopago', 'name' => 'setting.mercadopago')
    );
    /**
     * View the generic report
     * 
     * @return View
     */
    public function getSettings()
    {
        // Enums
        $enums = array(
            'auto_transfer_provider_payment' => Config::get('enum.auto_transfer_provider_payment'),
            'auto_transfer_schedule_at_after_selected_number_of_days' => Config::get('enum.auto_transfer_schedule_at_after_selected_number_of_days'),
            'payment' => Config::get('enum.payment'),
            'stripe_connect' => config('enum.stripe_connect'),
            'stripe_total_split_refund' => config('enum.stripe_total_split_refund')
        );

        //recupera settings
        $settings = [];
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
     *  Update each value on Settings table
     */
    public function saveSettings(GatewaysFormRequest $request)
    {
        $first_setting = Settings::first();
        foreach ($request->settings[$request->default_payment] as $key => $value) {
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

    /* public function getAppApiExample()
    {
        $teste = "Variavel teste";

        // Return data
        return new TesteResource([
            'teste' => $teste
        ]);
    } */
}
