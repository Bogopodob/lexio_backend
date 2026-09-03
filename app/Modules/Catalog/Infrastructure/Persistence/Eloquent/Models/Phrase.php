<?php

namespace App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\UuidInterface;

/**
 * @property UuidInterface id
 * @property string level
 * @property string phrase_type
 * @property ?string image_path
 * @property ?Carbon created_at
 * @property ?Carbon updated_at
 */
final class Phrase extends Model
{
    use HasUuids;

    protected $table = 'phrases';

    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'level',
        'phrase_type',
        'image_path',
        'created_at',
        'updated_at',
    ];
}
