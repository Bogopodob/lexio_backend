<?php

namespace App\Modules\User\Infrastructure\Http\Requests;

use App\Shared\Laravel\Infrastructure\Http\Request\AppFormRequest;

final class SendFriendRequestRequest extends AppFormRequest
{
    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'uuid', 'required_without:email'],
            'email' => ['nullable', 'email', 'max:255', 'required_without:user_id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge(['email' => mb_strtolower(trim((string) $this->input('email')))]);
        }
    }
}
