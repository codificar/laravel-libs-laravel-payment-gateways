<?php

use Illuminate\Database\Migrations\Migration;

class SettingCompensateProviderDays extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{		
		Settings::updateOrCreate(['key' => 'compensate_provider_days'], [
			'value' => '0', 
			'tool_tip' => 'Defina em quantos dias o prestador receberá o saldo em seu extrato de conta quando o pagamento é feito com cartão. Para o prestador receber no momento que for finalizado a solicitação, coloque 0. Se nenhum valor for selecionado, será considerado o tempo de compensação do gateway (geralmente 31 dias)',
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
		//
	}
}
