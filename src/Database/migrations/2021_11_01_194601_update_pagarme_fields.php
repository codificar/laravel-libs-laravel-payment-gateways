<?php

use Illuminate\Database\Migrations\Migration;

class UpdatePagarmeFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Settings::updateOrCreate(
            [
                'key' => 'pagarme_secret_key',
                'value' => '',
                'created_at' => now(),
                'updated_at' => now(),
                'tool_tip' => 'Pagarme secret key',
                'page' => '1',
                'category' => '6',
                'sub_category' => '0'
            ]
        );

        Settings::updateOrCreate(
            [
                'key' => 'pagarme_token',
                'value' => '',
                'created_at' => now(),
                'updated_at' => now(),
                'tool_tip' => 'Pagarme access token',
                'page' => '1',
                'category' => '6',
                'sub_category' => '0'
            ]
        );

        Settings::updateOrCreate(
            ['key' => 'ipag_product_title'],
            [
                'key' => 'gateway_product_title',
                'value' => '',
                'created_at' => now(),
                'updated_at' => now(),
                'tool_tip' => 'Gateway product title',
                'page' => '1',
                'category' => '6',
                'sub_category' => '0'
            ]
        );

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if($secret = Settings::whereKey('pagarme_secret_key')->first())
            $secret->delete();

        if($token = Settings::whereKey('pagarme_token')->first())
            $token->delete();

        Settings::updateOrCreate(
            ['key' => 'gateway_product_title'],
            [
                'key' => 'ipag_product_title',
                'value' => '',
                'created_at' => now(),
                'updated_at' => now(),
                'tool_tip' => 'Ipag product title',
                'page' => '1',
                'category' => '6',
                'sub_category' => '0'
            ]
        );

    }
}
