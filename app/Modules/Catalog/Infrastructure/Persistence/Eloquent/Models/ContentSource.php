<?php

namespace App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\UuidInterface;

/**
 * @property UuidInterface id
 * @property string type
 * @property string title
 * @property ?UuidInterface language_id
 * @property ?string level
 * @property ?string external_id
 * @property ?Carbon created_at
 * @property ?Carbon updated_at
 */
final class ContentSource extends Model
{
    use HasUuids;

    protected $table = 'content_sources';

    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'type',
        'title',
        'language_id',
        'level',
        'external_id',
        'created_at',
        'updated_at',
    ];
}
