<?php

namespace App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\UuidInterface;

/**
 * @property UuidInterface id
 * @property UuidInterface entry_id
 * @property ?string note
 * @property ?Carbon created_at
 * @property ?Carbon updated_at
 */
final class EntryMeaning extends Model
{
    use HasUuids;

    protected $table = 'entry_meanings';

    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'entry_id',
        'note',
        'created_at',
        'updated_at',
    ];
}
