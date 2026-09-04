<?php

namespace App\Modules\Learning\Infrastructure\Persistence\Database\Eloquent\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\UuidInterface;

/**
 * @property UuidInterface id
 * @property UuidInterface user_id
 * @property UuidInterface profile_id
 * @property UuidInterface achievement_id
 * @property int progress
 * @property bool unlocked
 * @property ?Carbon unlocked_at
 * @property ?Carbon created_at
 * @property ?Carbon updated_at
 */
final class UserAchievement extends Model
{
    use HasUuids;

    protected $table = 'user_achievements';

    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'user_id',
        'profile_id',
        'achievement_id',
        'progress',
        'unlocked',
        'unlocked_at',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'progress' => 'integer',
            'unlocked' => 'boolean',
            'unlocked_at' => 'datetime',
        ];
    }
}
