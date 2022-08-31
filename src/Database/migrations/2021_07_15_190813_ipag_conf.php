<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class IpagConf extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $ipagId         =   Settings::findObjectByKey('ipag_api_id');
        $ipagKey        =   Settings::findObjectByKey('ipag_api_key');
        $ipagToken      =   Settings::findObjectByKey('ipag_token');
        $ipagHookIsset  =   Settings::findObjectByKey('ipag_webhook_isset');

        if(!$ipagId)
            Settings::updateOrCreate(array('key' => 'ipag_api_id',
                'value'         => '',
                'tool_tip'      => 'Ipag API Id',
                'page'          => '1',
                'category'      => '6',
                'sub_category'  => '0',
            ));

        if(!$ipagKey)
            Settings::updateOrCreate(array('key' => 'ipag_api_key',
                'value'         => '',
                'tool_tip'      => 'Ipag API Key',
                'page'          => '1',
                'category'      => '6',
                'sub_category'  => '0',
            ));

        if(!$ipagToken)
            Settings::updateOrCreate(array('key' => 'ipag_token',
                'value'         => '',
                'tool_tip'      => 'Ipag Token',
                'page'          => '1',
                'category'      => '6',
                'sub_category'  => '0',
            ));

        if(!$ipagHookIsset)
            Settings::updateOrCreate(array('key' => 'ipag_webhook_isset',
                'value'         => '0',
                'tool_tip'      => 'Ipag WebHook Isset',
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
        $ipagId         =   Settings::findObjectByKey('ipag_api_id');
        $ipagKey        =   Settings::findObjectByKey('ipag_api_key');
        $ipagToken      =   Settings::findObjectByKey('ipag_token');
        $ipagHookIsset  =   Settings::findObjectByKey('ipag_webhook_isset');

        if(!$ipagId)
            $ipagId->delete();

        if(!$ipagKey)
            $ipagKey->delete();

        if(!$ipagToken)
            $ipagToken->delete();

        if(!$ipagHookIsset)
            $ipagHookIsset->delete();
    }
}
