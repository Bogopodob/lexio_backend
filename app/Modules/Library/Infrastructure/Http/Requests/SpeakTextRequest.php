<?php

namespace App\Modules\Library\Infrastructure\Http\Requests;

use App\Shared\Laravel\Infrastructure\Http\Request\AppFormRequest;

final class SpeakTextRequest extends AppFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'text' => ['required', 'string', 'min:1', 'max:200'],
            'lang' => ['nullable', 'string', 'size:2'],
        ];
    }
}
