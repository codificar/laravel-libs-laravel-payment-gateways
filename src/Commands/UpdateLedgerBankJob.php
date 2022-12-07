<?php

namespace Codificar\PaymentGateways\Commands;

use Codificar\PaymentGateways\Models\GatewaysLibModel;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateLedgerBankJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $ledgerBankAccounts;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($ledgerBankAccounts)
    {
        $this->ledgerBankAccounts = $ledgerBankAccounts;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        GatewaysLibModel::UpdateBankAccounts($this->ledgerBankAccounts);
    }
}
