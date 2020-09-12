<?php

namespace Codificar\PaymentGateways\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class GatewaysResource
 *
 * @package MotoboyApp
 *
 *
 * @OA\Schema(
 *         schema="GatewaysResource",
 *         type="object",
 *         description="Retorno ao salvar confs dos gateways",
 *         title="Generic Details Resource",
 *        allOf={
 *           @OA\Schema(ref="#/components/schemas/GatewaysResource"),
 *           @OA\Schema(
 *              required={"success", "request"},
 *           )
 *       }
 * )
 */
class GatewaysResource extends JsonResource
{

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {

        return [
            'success' => true
        ];
    }
}
