<?php

namespace App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\UuidInterface;

/**
 * @property UuidInterface $id
 * @property string code
 * @property string name
 * @property string native_name
 * @property string direction
 * @property bool is_active
 * @property int sort
 * @property ?Carbon created_at
 * @property ?Carbon updated_at
 */
final class Language extends Model
{
    use HasUuids;

    protected $table = 'languages';

    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'code',
        'name',
        'native_name',
        'direction',
        'is_active',
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
            'is_active' => 'boolean',
            'sort' => 'integer',
        ];
    }
}
