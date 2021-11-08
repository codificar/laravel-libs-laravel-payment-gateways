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
            ['key' => 'pagarme_encryption_key'],
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
            ['key' => 'pagarme_api_key'],
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
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Settings::updateOrCreate(
            ['key' => 'pagarme_secret_key'],
            [
                'key' => 'pagarme_encryption_key',
                'value' => '',
                'created_at' => now(),
                'updated_at' => now(),
                'tool_tip' => 'Pagarme encryption key',
                'page' => '1',
                'category' => '6',
                'sub_category' => '0'
            ]
        );

        Settings::updateOrCreate(
            ['key' => 'pagarme_token'],
            [
                'key' => 'pagarme_api_key',
                'value' => '',
                'created_at' => now(),
                'updated_at' => now(),
                'tool_tip' => 'Pagarme api key',
                'page' => '1',
                'category' => '6',
                'sub_category' => '0'
            ]
        );

        if($secret = Settings::whereKey('pagarme_secret_key')->first())
            $secret->delete();

        if($token = Settings::whereKey('pagarme_token')->first())
            $token->delete();
    }
}