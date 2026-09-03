<?php

namespace App\Modules\Catalog\Infrastructure\Http\Requests;

use App\Shared\Laravel\Infrastructure\Http\Request\AppFormRequest;

final class SearchEntriesRequest extends AppFormRequest
{
    public function rules(): array
    {
        return [
            'language_id' => ['required', 'uuid', 'exists:languages,id'],
            'query' => ['required', 'string', 'min:1', 'max:100'],
            'level' => ['nullable', 'string', 'size:2'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('query')) {
            $this->merge(['query' => trim((string) $this->input('query'))]);
        }
    }
}
