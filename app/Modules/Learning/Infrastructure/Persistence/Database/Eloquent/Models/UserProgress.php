<?php

namespace App\Modules\Learning\Infrastructure\Persistence\Database\Eloquent\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\UuidInterface;

/**
 * @property UuidInterface id
 * @property UuidInterface user_id
 * @property string learnable_type
 * @property UuidInterface learnable_id
 * @property UuidInterface profile_id
 * @property float easiness_factor
 * @property int interval_days
 * @property int repetition
 * @property int quality_last
 * @property ?Carbon next_review_at
 * @property ?Carbon last_reviewed_at
 * @property ?Carbon created_at
 * @property ?Carbon updated_at
 */
final class UserProgress extends Model
{
    use HasUuids;

    protected $table = 'user_progresses';

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
        'learnable_type',
        'learnable_id',
        'easiness_factor',
        'interval_days',
        'repetition',
        'quality_last',
        'next_review_at',
        'last_reviewed_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'next_review_at' => 'datetime',
        'last_reviewed_at' => 'datetime',
    ];
}
