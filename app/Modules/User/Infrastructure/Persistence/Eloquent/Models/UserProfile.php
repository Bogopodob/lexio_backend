<?php

namespace App\Modules\User\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

final class UserProfile extends Model
{
    protected $table = 'user_profiles';

    protected $primaryKey = 'user_id';

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'lastname',
        'surname',
        'avatar',
        'city',
        'birth_date',
        'tags',
        'reminder_schedule',
        'gender',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'tags' => 'array',
            'reminder_schedule' => 'array',
        ];
    }
}
