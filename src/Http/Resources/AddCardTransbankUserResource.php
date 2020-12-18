<?php

namespace Codificar\PaymentGateways\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class AddCardTransbankUserResource
 *
 * @package UberFretes
 *
 * @OA\Schema(
 *         schema="AddCardTransbankUserResource",
 *         type="object",
 *         description="Retorno da criação de um cartão pelo Usuário",
 *         title="Add Card User Resource",
 *        allOf={
 *           @OA\Schema(ref="#/components/schemas/AddCardTransbankUserResource"),
 *           @OA\Schema(
 *              required={"success", "data"},
 *               @OA\Property(property="success", format="boolean", type="boolean"),
 *               @OA\Property(property="data", format="object", type="object")
 *           )
 *       }
 * )
 */
class AddCardTransbankUserResource extends JsonResource {

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request) {

        return [
            'success' => true,
            'link' => isset($this['link']) ? $this['link'] : null,
            /* 'status' => isset($this['status']) ? $this['status'] : null,
            'description' => isset($this['description']) ? $this['description'] : null,
            'card' => isset($this['payment']) ? $this['payment'] : null */
        ];
    }

}
