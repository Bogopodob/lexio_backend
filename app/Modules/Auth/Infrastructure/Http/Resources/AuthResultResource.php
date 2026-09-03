<?php

namespace App\Modules\Auth\Infrastructure\Http\Resources;

use App\Modules\Auth\Application\DTO\AuthResult;
use Illuminate\Http\Resources\Json\JsonResource;

final class AuthResultResource extends JsonResource
{
    public function __construct(private readonly AuthResult $result)
    {
        parent::__construct($result);
    }

    public function toArray($request): array
    {
        return [
            'token' => $this->result->token,
            'expires_at' => $this->result->expiresAt->format(DATE_ATOM),
            'expires_in' => $this->result->expiresIn,
            'user' => [
                'id' => (string) $this->result->user->id,
                'name' => $this->result->user->name,
                'email' => (string) $this->result->user->email,
            ],
        ];
    }
}
