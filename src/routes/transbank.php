<?php
Route::group(array('namespace' => 'Codificar\PaymentGateways\Http\Controllers'), function () {
  Route::group(['prefix' => 'libs/gateways/user', 'middleware' => 'auth.user_api:api'], function () {
    /**
     * @OA\Post(path="/api/v1/user/transbank/add_card",
     * tags={"user"},
     *      operationId="addCardTransbankd",
     *      description="Adiciona cartão do usuário naTransbank",
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
     *          @OA\JsonContent(ref="#/components/schemas/AddCardBancardUserResource")
     *      ),
     *      @OA\Response(
     *          response="402",
     *          description="Form request validation error. Invalid input."
     *      ),
     * )
     */
    Route::post('/transbank/add_card', 'TransbankController@addCard');
  });


  Route::group(['prefix' => 'libs/gateways'], function () {
    //rota create card transbank
    Route::get('transbank/card/{tbk_token}/{url_webpay}', 'TransbankController@getCardLink');
    Route::post('transbank/card_return', 'TransbankController@returnCard');
  });
});