<?php

namespace Codificar\PaymentGateways\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;


// Importar models
use Codificar\PaymentGateways\Models\Generic;

// Importar Resource
use Codificar\PaymentGateways\Http\Resources\TesteResource;


use Input, Validator, View, Response;
use Provider, Settings, Ledger, Finance, Bank, LedgerBankAccount;

class GenericController extends Controller {

    

    /**
     * View the generic report
     * 
     * @return View
     */
    public function getExampleVuejs() {

        $adminsList = Generic::getAdminList();

        return View::make('generic::example_vuejs')
                    ->with([
                        'admins_list' => $adminsList
                    ]);
    
    }


    public function getAppApiExample()
    {
        $teste = "Variavel teste";
        
        // Return data
		return new TesteResource([
			'teste' => $teste
		]);
    }

}