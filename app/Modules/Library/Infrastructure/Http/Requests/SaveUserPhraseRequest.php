<?php

namespace App\Modules\Library\Infrastructure\Http\Requests;

use App\Shared\Laravel\Infrastructure\Http\Request\AppFormRequest;

final class SaveUserPhraseRequest extends AppFormRequest
{
    public function rules(): array
    {
        return [
            'category_id' => ['nullable', 'uuid', 'exists:categories,id'],
            'phrase_type' => ['nullable', 'string', 'max:50'],
            'image_path' => ['nullable', 'string', 'max:2048'],
            'translations' => ['required', 'array', 'min:1'],
            'translations.*.language_id' => ['required', 'uuid', 'exists:languages,id'],
            'translations.*.text' => ['required', 'string', 'min:1', 'max:2000'],
            'translations.*.transcription' => ['nullable', 'string', 'max:2000'],
            'translations.*.notes' => ['nullable', 'string', 'max:2000'],
            'translations.*.audio_path' => ['nullable', 'string', 'max:2048'],
        ];
    }
}
