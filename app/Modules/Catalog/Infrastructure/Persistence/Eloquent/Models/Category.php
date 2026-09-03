<?php

namespace App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\UuidInterface;

/**
 * @property UuidInterface $id
 * @property UuidInterface parent_id
 * @property ?UuidInterface user_id
 * @property string slug
 * @property bool is_system
 * @property string type
 * @property ?string color
 * @property ?string icon
 * @property int sort
 * @property ?Carbon created_at
 * @property ?Carbon updated_at
 */
final class Category extends Model
{
    use HasUuids;

    protected $table = 'categories';

    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'parent_id',
        'user_id',
        'slug',
        'is_system',
        'type',
        'color',
        'icon',
        'sort',
        'created_at',
        'updated_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'sort' => 'integer',
        ];
    }
}
