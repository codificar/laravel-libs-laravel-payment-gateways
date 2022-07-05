<?php

namespace Codificar\PaymentGateways\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class WebhookResource
 *
 * @package MotoboyApp
 *
 *
 * @OA\Schema(
 *         schema="WebhookResource",
 *         type="object",
 *         description="Retorno ao recuperar os webhooks",
 *         title="Generic Details Resource",
 *        allOf={
 *           @OA\Schema(ref="#/components/schemas/WebhookResource"),
 *           @OA\Schema(
 *              required={"success", "request"},
 *           )
 *       }
 * )
 */
class WebhookResource extends JsonResource
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
            'success' => $this['success'],
            'webhooks' => $this['webhooks'],
            'message' => $this['message'],
        ];
    }
}
