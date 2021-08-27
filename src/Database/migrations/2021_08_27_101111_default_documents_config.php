<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DefaultDocumentsConfig extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Settings::updateOrCreate(array('key' => 'default_doc_identification',
            'value'         => '1', 
            'tool_tip'      => '', 
            'page'          => '1',
            'category'      => '6',
            'sub_category'  => '0',
        ));

        Settings::updateOrCreate(array('key' => 'default_doc_activity',
            'value'         => '1', 
            'tool_tip'      => '', 
            'page'          => '1',
            'category'      => '6',
            'sub_category'  => '0',
        ));

        Settings::updateOrCreate(array('key' => 'default_doc_address',
            'value'         => '1', 
            'tool_tip'      => '', 
            'page'          => '1',
            'category'      => '6',
            'sub_category'  => '0',
        ));

        Settings::updateOrCreate(array('key' => 'default_doc_document',
            'value'         => '1', 
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
        $identification = Settings::findObjectByKey('default_doc_identification');
        $activity       = Settings::findObjectByKey('default_doc_activity');
        $address        = Settings::findObjectByKey('default_doc_address');
        $document       = Settings::findObjectByKey('default_doc_document');

        if($identification)
            $identification->delete();

        if($activity)
            $activity->delete();

        if($address)
            $address->delete();

        if($document)
            $document->delete();
    }
}
