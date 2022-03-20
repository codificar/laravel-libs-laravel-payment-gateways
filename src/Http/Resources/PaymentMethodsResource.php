<?php

namespace Codificar\PaymentGateways\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class AddCardBancardResource
 *
 * @package MotoboyApp
 *
 * @author  Alexandre Souza <andre.gustavo@codificar.com.br>
 *
 * @OA\Schema(
 *         schema="PaymentMethodsResource",
 *         type="object",
 *         description="Retorno da criação de um cartão",
 *         title="Add Card User Resource",
 *        allOf={
 *           @OA\Schema(ref="#/components/schemas/PaymentMethodsResource"),
 *           @OA\Schema(
 *              required={"success", "data"},
 *               @OA\Property(property="success", format="boolean", type="boolean"),
 *               @OA\Property(property="data", format="object", type="object")
 *           )
 *       }
 * )
 */

const FIRST = 0;
const SECOND = 1;
const TRHEE = 2;

class PaymentMethodsResource extends JsonResource {

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request) {
        return [
            'payment_card' => [ 'is_active' => $this[SECOND][FIRST],
                'payment_value' => $this[FIRST][SECOND]
                ['payment_card_value']],

            'payment_money' => [ 
                'is_active' => $this[FIRST][FIRST],
                'payment_value' => $this[SECOND][SECOND]
                ['money_value']],

            'payment_balance' => [ 'is_active' => $this[TRHEE][FIRST],
                'payment_value' => $this[TRHEE][SECOND]
                ['balance_value']],
        ];
    }

}
