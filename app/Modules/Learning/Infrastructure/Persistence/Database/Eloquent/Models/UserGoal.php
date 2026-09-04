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
 * @property string title
 * @property ?string desc
 * @property ?string color
 * @property int progress
 * @property int sort
 * @property ?Carbon created_at
 * @property ?Carbon updated_at
 */
final class UserGoal extends Model
{
    use HasUuids;

    protected $table = 'user_goals';

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
        'title',
        'desc',
        'color',
        'progress',
        'sort',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'progress' => 'integer',
            'sort' => 'integer',
        ];
    }
}
