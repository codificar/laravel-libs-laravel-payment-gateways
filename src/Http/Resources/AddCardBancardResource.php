<?php

namespace Codificar\PaymentGateways\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class AddCardBancardResource
 *
 * @package MotoboyApp
 *
 * @author  André Gustavo <andre.gustavo@codificar.com.br>
 *
 * @OA\Schema(
 *         schema="AddCardBancardResource",
 *         type="object",
 *         description="Retorno da criação de um cartão",
 *         title="Add Card User Resource",
 *        allOf={
 *           @OA\Schema(ref="#/components/schemas/AddCardBancardResource"),
 *           @OA\Schema(
 *              required={"success", "data"},
 *               @OA\Property(property="success", format="boolean", type="boolean"),
 *               @OA\Property(property="data", format="object", type="object")
 *           )
 *       }
 * )
 */
class AddCardBancardResource extends JsonResource {

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request) {

        return [
            'success' => true,
            'iframe' => isset($this['iframe']) ? $this['iframe'] : null,
            'status' => isset($this['status']) ? $this['status'] : null,
            'description' => isset($this['description']) ? $this['description'] : null,
            'card' => isset($this['payment']) ? $this['payment'] : null
        ];
    }

}
