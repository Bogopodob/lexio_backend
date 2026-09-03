<?php

namespace App\Modules\Learning\Infrastructure\Persistence\Database\Eloquent\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\UuidInterface;

/**
 * @property UuidInterface id
 * @property UuidInterface user_id
 * @property UuidInterface target_language_id
 * @property UuidInterface native_language_id
 * @property string level
 * @property int daily_goal
 * @property bool is_active
 * @property ?Carbon created_at
 * @property ?Carbon updated_at
 */
final class UserLanguageProfile extends Model
{
    use HasUuids;

    protected $table = 'user_language_profiles';

    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'user_id',
        'target_language_id',
        'native_language_id',
        'level',
        'daily_goal',
        'is_active',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'daily_goal' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
