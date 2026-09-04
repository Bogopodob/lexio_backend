<?php

namespace App\Modules\Learning\Infrastructure\Http\Requests;

use App\Shared\Laravel\Infrastructure\Http\Request\AppFormRequest;

final class SaveGoalRequest extends AppFormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:120'],
            'desc' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:15'],
            'progress' => ['nullable', 'integer', 'min:0', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['title', 'desc'] as $field) {
            if ($this->has($field) && $this->input($field) !== null) {
                $this->merge([$field => trim((string) $this->input($field))]);
            }
        }
    }
}
