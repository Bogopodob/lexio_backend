<?php

namespace App\Modules\Library\Infrastructure\Persistence\Eloquent\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\UuidInterface;

/**
 * @property UuidInterface id
 * @property UuidInterface user_id
 * @property ?UuidInterface category_id
 * @property string phrase_type
 * @property ?Carbon created_at
 * @property ?Carbon updated_at
 */
final class UserPhrase extends Model
{
    use HasUuids;

    protected $table = 'user_phrases';

    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'user_id',
        'category_id',
        'phrase_type',
        'created_at',
        'updated_at',
    ];
}
