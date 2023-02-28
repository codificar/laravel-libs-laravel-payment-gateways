<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGatewayLedgerBankAccount extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $gateway = \Settings::findByKey('default_payment', 'cielo');
        $tableName = 'ledger_bank_account';

        Schema::table($tableName, function (Blueprint $table) use ($tableName) {
            if (!Schema::hasColumn($tableName, 'gateway')) {
                $table->string('gateway', 50)->default('cielo')->nullable();
            }
        });

        \DB::table($tableName)->update(['gateway' => $gateway]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ledger_bank_account', function (Blueprint $table) {
            if (Schema::hasColumn('ledger_bank_account', 'gateway')) {
                $table->dropColumn('gateway');
            }
        });
    }
}
