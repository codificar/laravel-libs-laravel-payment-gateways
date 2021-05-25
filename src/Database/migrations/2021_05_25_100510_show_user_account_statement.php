<?php

use Illuminate\Database\Migrations\Migration;

class ShowUserAccountStatement extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{		
		Settings::updateOrCreate(['key' => 'show_user_account_statement'], [
			'value' => '1', 
			'tool_tip' => '',
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
