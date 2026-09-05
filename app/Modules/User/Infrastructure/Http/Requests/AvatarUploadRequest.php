<?php

namespace App\Modules\User\Infrastructure\Http\Requests;

use App\Shared\Laravel\Infrastructure\Http\Request\AppFormRequest;
use Illuminate\Validation\Rules\File;

final class AvatarUploadRequest extends AppFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'avatar' => [
                'required',
                'file',
                File::image()->types(['jpeg', 'png'])->max(5 * 1024),
                'dimensions:max_width=4096,max_height=4096',
            ],
        ];
    }
}
