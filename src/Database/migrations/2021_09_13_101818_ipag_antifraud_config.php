<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class IpagAntifraudConfig extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Settings::updateOrCreate(array('key' => 'ipag_antifraud',
            'value'         => '0', 
            'tool_tip'      => '', 
            'page'          => '1',
            'category'      => '6',
            'sub_category'  => '0',
        ));
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $antifraud = Settings::findObjectByKey('ipag_antifraud');

        if($antifraud)
            $antifraud->delete();
    }
}
