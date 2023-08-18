<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGatewaySplitTaxesToSettings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
	{		
		Settings::updateOrCreate(['key' => 'gateway_split_taxes'], [
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
        $setting = Settings::where(['key' => 'gateway_split_taxes'])->first();
        if($setting){
            $setting->delete();
        }
    }
}
