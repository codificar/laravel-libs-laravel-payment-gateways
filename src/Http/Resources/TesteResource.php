<?php

namespace Codificar\Generic\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class TesteResource
 *
 * @package MotoboyApp
 *
 *
 * @OA\Schema(
 *         schema="TesteResource",
 *         type="object",
 *         description="Retorno Retorno do relatorio de saques do prestador",
 *         title="Generic Details Resource",
 *        allOf={
 *           @OA\Schema(ref="#/components/schemas/TesteResource"),
 *           @OA\Schema(
 *              required={"success", "request"},
 *           )
 *       }
 * )
 */
class TesteResource extends JsonResource {

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request) {

        return [
            'success' => true,
            'teste' => $this['teste'],
        ];
    }

}
