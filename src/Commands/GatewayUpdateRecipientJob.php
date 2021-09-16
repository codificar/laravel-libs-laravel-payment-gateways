<?php

namespace Codificar\PaymentGateways\Commands;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use Codificar\PaymentGateways\Models\GatewaysLibModel;

class GatewayUpdateRecipientJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $ledgerAccounts;
    private $remainingAccounts;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($ledgerAccounts)
    {
        $this->remainingAccounts = array_splice($ledgerAccounts, 300);
        $this->ledgerAccounts = $ledgerAccounts;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try
        {
            GatewaysLibModel::gatewayUpdateBankAccounts($this->ledgerAccounts);

            if(count($this->remainingAccounts))
                $this->dispatch($this->remainingAccounts);
            else
                $this->info("Update recipients by job finish");

        } catch (\Throwable $th) {
            Log::error(
                "Fail update recipients by job. Ledgers: " . $this->ledgerAccounts . 
                " Error: " . $th->getMessage()
            );

			$this->error("Fail update recipients by job");
        }
    }
}