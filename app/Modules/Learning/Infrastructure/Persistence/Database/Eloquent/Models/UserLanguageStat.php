<?php

namespace App\Modules\Learning\Infrastructure\Persistence\Database\Eloquent\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\UuidInterface;

/**
 * @property UuidInterface id
 * @property UuidInterface profile_id
 * @property int words_learned
 * @property int streak_days
 * @property int best_streak
 * @property int xp
 * @property float accuracy
 * @property ?Carbon last_activity_at
 * @property ?Carbon created_at
 * @property ?Carbon updated_at
 */
final class UserLanguageStat extends Model
{
    use HasUuids;

    protected $table = 'user_language_stats';

    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'profile_id',
        'words_learned',
        'streak_days',
        'best_streak',
        'xp',
        'accuracy',
        'last_activity_at',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'words_learned' => 'integer',
            'streak_days' => 'integer',
            'best_streak' => 'integer',
            'xp' => 'integer',
            'accuracy' => 'float',
            'last_activity_at' => 'datetime',
        ];
    }
}
