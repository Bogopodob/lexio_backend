<?php

namespace App\Modules\Auth\Infrastructure\Http\Resources;

use App\Modules\Auth\Application\DTO\RequestEmailCodeResult;
use Illuminate\Http\Resources\Json\JsonResource;

final class RequestEmailCodeResultResource extends JsonResource
{
    public function __construct(private readonly RequestEmailCodeResult $result)
    {
        parent::__construct($result);
    }

    public function toArray($request): array
    {
        return [
            'success' => true,
            'data' => [
                'expires_in' => $this->result->ttl,
                'debug_code' => $this->result->debugCode,
                'delivery' => [
                    'channel' => $this->result->deliveryChannel,
                    'provider' => $this->result->deliveryProvider,
                ],
            ],
        ];
    }
}
