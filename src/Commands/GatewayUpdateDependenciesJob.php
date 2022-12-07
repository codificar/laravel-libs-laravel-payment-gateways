<?php

namespace Codificar\PaymentGateways\Commands;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use Codificar\PaymentGateways\Models\GatewaysLibModel;

class GatewayUpdateDependenciesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;



    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {

    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        GatewaysLibModel::gatewayUpdateCards();
        //GatewaysLibModel::gatewayUpdateBankAccounts();
    }
}