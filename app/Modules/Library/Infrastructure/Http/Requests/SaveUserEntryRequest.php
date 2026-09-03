<?php

namespace App\Modules\Library\Infrastructure\Http\Requests;

use App\Shared\Laravel\Infrastructure\Http\Request\AppFormRequest;

final class SaveUserEntryRequest extends AppFormRequest
{
    public function rules(): array
    {
        return [
            'category_id' => ['nullable', 'uuid', 'exists:categories,id'],
            'image_path' => ['nullable', 'string', 'max:2048'],
            'translations' => ['required', 'array', 'min:1'],
            'translations.*.language_id' => ['required', 'uuid', 'exists:languages,id'],
            'translations.*.text' => ['required', 'string', 'min:1', 'max:255'],
            'translations.*.transcription' => ['nullable', 'string', 'max:255'],
            'translations.*.part_of_speech' => ['nullable', 'string', 'max:50'],
            'translations.*.notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
