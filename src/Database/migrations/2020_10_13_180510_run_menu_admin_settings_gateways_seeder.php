<?php

use Illuminate\Database\Migrations\Migration;

class RunMenuAdminSettingsGatewaysSeeder extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{		
		$addMenu = Permission::updateOrCreate(array('name' => 'admin_settings_gateways', 'parent_id' => 2319, 'order' => 908, 'is_menu' => 1, 'url' => '/admin/settings/gateways'));
		DB::statement("UPDATE `permission` SET `icon`='mdi mdi-credit-card' WHERE `id` = $addMenu->id ;");
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
