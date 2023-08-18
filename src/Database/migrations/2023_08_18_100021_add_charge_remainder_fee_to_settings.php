<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddChargeRemainderFeeToSettings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
       Settings::updateOrCreate(['key' => 'charge_remainder_fee'], [
			'value' => '0', 
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
        $setting = Settings::where(['key' => 'charge_remainder_fee'])->first();
        if($setting){
            $setting->delete();
        }
    }
}