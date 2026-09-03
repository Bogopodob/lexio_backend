<?php

namespace App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\UuidInterface;

/**
 * @property UuidInterface id
 * @property UuidInterface entry_id
 * @property UuidInterface category_id
 * @property string form_type
 * @property ?Carbon created_at
 * @property ?Carbon updated_at
 */
final class EntryCategory extends Model
{
    use HasUuids;

    protected $table = 'entry_category';

    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'entry_id',
        'category_id',
        'created_at',
        'updated_at',
    ];
}
