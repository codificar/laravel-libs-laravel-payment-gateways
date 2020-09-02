<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class GenericExample extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //Migration de exemplo. Foi comentada para nao criar nenhuma tabela/coluna sem necessidade.

        // Schema::create('teste', function(Blueprint $table) {
        //     $table->increments('id');
        //     $table->integer('teste');
        // });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

    }
}
