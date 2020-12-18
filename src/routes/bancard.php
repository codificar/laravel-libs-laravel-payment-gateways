<?php

// Rotas apis
Route::group(array('namespace' => 'Codificar\PaymentGateways\Http\Controllers'), function () {

    Route::group(['prefix' => 'libs/gateways/user', 'middleware' => 'auth.user_api:api'], function () {
        /**
         * @OA\Post(path="/libs/gateways/user/bancard/add_card",
         * tags={"user"},
         *      operationId="addCardBancard",
         *      description="Adiciona cartão do usuário na Bancard",
         *      @OA\Parameter(name="user_id",
         *          description="User id",
         *          in="query",
         *          required=true,
         *          @OA\Schema(type="integer")
         *      ),
         *      @OA\Parameter(name="token",
         *          description="Token do User",
         *          in="query",
         *          required=true,
         *          @OA\Schema(type="string")
         *      ),
         *      @OA\Response(response="200",
         *          description="Resource for routes",
         *          @OA\JsonContent(ref="#/components/schemas/AddCardBancardResource")
         *      ),
         *      @OA\Response(
         *          response="402",
         *          description="Form request validation error. Invalid input."
         *      ),
         * )
         */
        Route::post('/bancard/add_card', array('as' => 'userSaveCardBancard', 'uses' => 'BancardController@addCard'));  
    });
    
    Route::group(['prefix' => 'libs/gateways/provider', 'middleware' => 'auth.provider_api:api'], function () {
        /**
         * @OA\Post(path="/libs/gateways/provider/bancard/add_card",
         * tags={"provider"},
         *      operationId="addCardBancard",
         *      description="Adiciona cartão do provider na Bancard",
         *      @OA\Parameter(name="provider_id",
         *          description="Provider id",
         *          in="query",
         *          required=true,
         *          @OA\Schema(type="integer")
         *      ),
         *      @OA\Parameter(name="token",
         *          description="Token do provider",
         *          in="query",
         *          required=true,
         *          @OA\Schema(type="string")
         *      ),
         *      @OA\Response(response="200",
         *          description="Resource for routes",
         *          @OA\JsonContent(ref="#/components/schemas/AddCardBancardResource")
         *      ),
         *      @OA\Response(
         *          response="402",
         *          description="Form request validation error. Invalid input."
         *      ),
         * )
         */
        Route::post('/bancard/add_card', array('as' => 'providerSaveCardBancard', 'uses' => 'BancardController@addCard'));
    });
    
    Route::group(['prefix' => 'libs/gateways'], function () {
        //rota create card iframe bancard
        Route::get('/bancard/iframe_card/{process_id}', 'BancardController@getIframeCard');
        
        //rota return
        Route::get('/bancard/return/{user_id}/{provider_id}', 'BancardController@getReturn');
    });
});
