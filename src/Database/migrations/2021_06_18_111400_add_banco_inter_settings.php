<?php

use Illuminate\Database\Migrations\Migration;

class AddBancoInterSettings extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{		
		Settings::updateOrCreate(['key' => 'banco_inter_account'], [
			'value' => '0', 
			'tool_tip' => 'Banco Inter account',
			'page' => 1,
			'category' => 2,
			'sub_category' => 0
		]);

		Settings::updateOrCreate(['key' => 'cnpj_for_banco_inter'], [
			'value' => '0', 
			'tool_tip' => 'CNPJ Beneficiario',
			'page' => 1,
			'category' => 2,
			'sub_category' => 0
		]);
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		$account = Settings::findObjectByKey('banco_inter_account');
        if (!$account) {
            $account->delete();
        }
		$cnpj = Settings::findObjectByKey('cnpj_for_banco_inter');
        if (!$cnpj) {
            $cnpj->delete();
        }
	}
}
