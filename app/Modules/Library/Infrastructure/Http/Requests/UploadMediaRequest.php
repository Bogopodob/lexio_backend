<?php

namespace App\Modules\Library\Infrastructure\Http\Requests;

use App\Shared\Laravel\Infrastructure\Http\Request\AppFormRequest;

final class UploadMediaRequest extends AppFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'kind' => ['required', 'string', 'in:image,audio'],
            'file' => [
                'required',
                'file',
                'max:10240',
            ],
        ];
    }
}
