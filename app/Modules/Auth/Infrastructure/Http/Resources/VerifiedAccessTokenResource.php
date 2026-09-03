<?php

namespace App\Modules\Auth\Infrastructure\Http\Resources;

use App\Modules\Auth\Application\DTO\VerifiedAccessTokenResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class VerifiedAccessTokenResource extends JsonResource
{
    public function __construct(private readonly VerifiedAccessTokenResult $result)
    {
        parent::__construct($result);
    }

    public function toArray(Request $request): array
    {
        return [
            'user' => [
                'id' => $this->result->subject,
                'email' => $this->result->email,
                'name' => $this->result->name,
            ],
            'token' => [
                'subject' => $this->result->subject,
                'expires_at' => $this->result->expiresAt->format(DATE_ATOM),
            ],
        ];
    }
}
