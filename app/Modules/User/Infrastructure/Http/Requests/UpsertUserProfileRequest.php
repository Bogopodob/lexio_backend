<?php

namespace App\Modules\User\Infrastructure\Http\Requests;

use App\Shared\Laravel\Infrastructure\Http\Request\AppFormRequest;

final class UpsertUserProfileRequest extends AppFormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'lastname' => ['nullable', 'string', 'max:255'],
            'surname' => ['nullable', 'string', 'max:255'],
            'avatar' => ['nullable', 'string', 'max:2048'],
            'city' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date_format:Y-m-d'],
            'tags' => ['nullable', 'array', 'max:6'],
            'tags.*' => ['string', 'max:20'],
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['name', 'lastname', 'surname', 'avatar', 'city'] as $field) {
            if ($this->has($field)) {
                $this->merge([$field => trim((string) $this->input($field))]);
            }
        }
    }
}
