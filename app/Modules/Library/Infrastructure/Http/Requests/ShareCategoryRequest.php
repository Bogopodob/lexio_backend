<?php

namespace App\Modules\Library\Infrastructure\Http\Requests;

use App\Shared\Laravel\Infrastructure\Http\Request\AppFormRequest;

final class ShareCategoryRequest extends AppFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'uuid', 'exists:categories,id'],
            'friend_user_id' => ['required', 'uuid', 'exists:users,id'],
        ];
    }
}
