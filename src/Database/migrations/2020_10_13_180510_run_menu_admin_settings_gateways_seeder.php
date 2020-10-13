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
		//Artisan::call('db:seed', ['--class' => 'GatewaysPermissionSeeder', '--force' => true]);
		
		$admin = Permission::updateOrCreate(array('id' => 6109), array('name' => 'admin_settings_gateways', 'parent_id' => 2319, 'order' => 908, 'is_menu' => 1, 'url' => '/admin/settings/gateways', 'icon' => 'mdi mdi-credit-card'));
		$profile = Profile::find(1);
		$profile ? $profile->permissions()->attach($admin->id) : null;
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
