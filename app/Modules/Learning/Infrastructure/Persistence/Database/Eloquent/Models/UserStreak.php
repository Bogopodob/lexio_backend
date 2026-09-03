<?php

namespace App\Modules\Learning\Infrastructure\Persistence\Database\Eloquent\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\UuidInterface;

/**
 * @property UuidInterface id
 * @property UuidInterface user_id
 * @property ?UuidInterface profile_id
 * @property Carbon date
 * @property int words_reviewed
 * @property int words_new
 * @property ?Carbon created_at
 * @property ?Carbon updated_at
 */
final class UserStreak extends Model
{
    use HasUuids;

    protected $table = 'user_streaks';

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
        'date',
        'words_reviewed',
        'words_new',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'date' => 'datetime',
    ];
}
