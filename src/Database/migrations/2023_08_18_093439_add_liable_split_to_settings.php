<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLiableSplitToSettings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
       Settings::updateOrCreate(['key' => 'liable_split'], [
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
        $setting = Settings::where(['key' => 'liable_split'])->first();
        if($setting){
            $setting->delete();
        }
    }
}

