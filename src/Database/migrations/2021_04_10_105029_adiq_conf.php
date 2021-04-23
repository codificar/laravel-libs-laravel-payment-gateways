<?php

use Illuminate\Database\Migrations\Migration;

class AdiqConf extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $clientId = Settings::findObjectByKey('adiq_client_id');
        $clientSecret = Settings::findObjectByKey('adiq_client_secret');
        $token = Settings::findObjectByKey('adiq_token');

        // $registerUsername = Settings::findObjectByKey('adiq_register_username');
        // $registerPassword = Settings::findObjectByKey('adiq_register_password');
        // $registerToken = Settings::findObjectByKey('adiq_register_token');

        //Creation of settings values
        if (!$clientId) {
            Settings::updateOrCreate(array('key' => 'adiq_client_id',
                'value'         => '', 
                'tool_tip'      => 'Adiq Client Id', 
                'page'          => '1',
                'category'      => '6',
                'sub_category'  => '0',
            ));
        }
        
        if (!$clientSecret) {
            Settings::updateOrCreate(array('key' => 'adiq_client_secret',
                'value'         => '', 
                'tool_tip'      => 'Adiq Client Secret', 
                'page'          => '1',
                'category'      => '6',
                'sub_category'  => '0',
            ));    
        }

        if (!$token) {
            Settings::updateOrCreate(array('key' => 'adiq_token',
                'value'         => '', 
                'tool_tip'      => 'Adiq Token', 
                'page'          => '1',
                'category'      => '6',
                'sub_category'  => '0',
            ));    
        }

        // if (!$registerUsername) {
        //     Settings::updateOrCreate(array('key' => 'adiq_register_username',
        //         'value'         => '', 
        //         'tool_tip'      => 'Adiq Register Username', 
        //         'page'          => '1',
        //         'category'      => '6',
        //         'sub_category'  => '0',
        //     ));    
        // }

        // if (!$registerPassword) {
        //     Settings::updateOrCreate(array('key' => 'adiq_register_password',
        //         'value'         => '', 
        //         'tool_tip'      => 'Adiq Register Password', 
        //         'page'          => '1',
        //         'category'      => '6',
        //         'sub_category'  => '0',
        //     ));    
        // }

        // if (!$registerToken) {
        //     Settings::updateOrCreate(array('key' => 'adiq_register_token',
        //         'value'         => '', 
        //         'tool_tip'      => 'Adiq Register Token', 
        //         'page'          => '1',
        //         'category'      => '6',
        //         'sub_category'  => '0',
        //     ));    
        // }

        $this->command->info('Adiq Settings updated!');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $clientId = Settings::findObjectByKey('adiq_client_id');
        $clientSecret = Settings::findObjectByKey('adiq_client_secret');
        $token = Settings::findObjectByKey('adiq_token');

        //Creation of settings values
        if (!$clientId) {
            $clientId->delete();
        }
        
        if (!$clientSecret) {
            $clientSecret->delete();
        }

        if (!$token) {
            $token->delete();  
        }
    }
}
