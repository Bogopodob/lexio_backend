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
 * @property string source
 * @property string status
 * @property int total
 * @property int answered
 * @property int correct
 * @property int xp_earned
 * @property ?Carbon started_at
 * @property ?Carbon finished_at
 * @property ?Carbon created_at
 * @property ?Carbon updated_at
 */
final class StudySession extends Model
{
    use HasUuids;

    protected $table = 'study_sessions';

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
        'source',
        'category_id',
        'status',
        'total',
        'answered',
        'correct',
        'xp_earned',
        'started_at',
        'finished_at',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'integer',
            'answered' => 'integer',
            'correct' => 'integer',
            'xp_earned' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }
}
