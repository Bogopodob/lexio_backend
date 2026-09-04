<?php

namespace App\Modules\Learning\Infrastructure\Persistence\Database\Eloquent\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\UuidInterface;

/**
 * @property UuidInterface id
 * @property string code
 * @property string title
 * @property ?string desc
 * @property string condition
 * @property string rarity
 * @property ?string color
 * @property int reward_xp
 * @property string rule_type
 * @property int rule_target
 * @property ?array rule_extra
 * @property int sort
 * @property ?Carbon created_at
 * @property ?Carbon updated_at
 */
final class Achievement extends Model
{
    use HasUuids;

    protected $table = 'achievements';

    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'code',
        'title',
        'desc',
        'condition',
        'rarity',
        'color',
        'reward_xp',
        'rule_type',
        'rule_target',
        'rule_extra',
        'sort',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'reward_xp' => 'integer',
            'rule_target' => 'integer',
            'rule_extra' => 'array',
            'sort' => 'integer',
        ];
    }
}
