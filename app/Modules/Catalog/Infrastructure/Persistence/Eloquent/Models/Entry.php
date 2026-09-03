<?php

namespace App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\UuidInterface;

/**
 * @property UuidInterface id
 * @property ?string image_path
 * @property string level
 * @property int frequency_rank
 * @property ?Carbon created_at
 * @property ?Carbon updated_at
 */
final class Entry extends Model
{
    use HasUuids;

    protected $table = 'entries';

    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'image_path',
        'level',
        'frequency_rank',
        'created_at',
        'updated_at',
    ];
}
