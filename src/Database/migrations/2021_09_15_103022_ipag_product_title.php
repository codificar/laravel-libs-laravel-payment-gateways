<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class IpagProductTitle extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Settings::updateOrCreate(array('key' => 'ipag_product_title',
            'value'         => 'Serviço de mobilidade', 
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
        $productTitle = Settings::findObjectByKey('ipag_product_title');

        if($productTitle)
            $productTitle->delete();
    }
}
