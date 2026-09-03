<?php

namespace App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\UuidInterface;

/**
 * @property UuidInterface id
 * @property ?UuidInterface entry_id
 * @property ?UuidInterface entry_translation_id
 * @property string type
 * @property string path
 * @property string source
 * @property ?string source_title
 * @property ?UuidInterface content_source_id
 * @property int sort
 * @property ?Carbon created_at
 * @property ?Carbon updated_at
 */
final class EntryMedia extends Model
{
    use HasUuids;

    protected $table = 'entry_media';

    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'entry_id',
        'entry_translation_id',
        'type',
        'path',
        'source',
        'source_title',
        'content_source_id',
        'sort',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'sort' => 'integer',
        ];
    }
}
