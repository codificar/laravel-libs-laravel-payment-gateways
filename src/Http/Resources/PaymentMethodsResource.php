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
class PaymentMethodsResource extends JsonResource {

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request) {
        // dd($this[1][1]['money_value']);
        return [
            'payment_money' => [ 'is_active' => $this[0][0] , 'payment_value' => $this[1][1]['money_value']],
            'payment_card' => [ 'is_active' => $this[1][0] , 'payment_value' => $this[0][1]['payment_card_value']],
            'payment_balance' => [ 'is_active' => $this[2][0] , 'payment_value' => $this[2][1]['balance_value']],
        ];
    }

}
