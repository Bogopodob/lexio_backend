<?php

namespace App\Shared\Laravel\Infrastructure\Persistence\Eloquent\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Ramsey\Uuid\UuidInterface;

/**
 * @property UuidInterface id
 * @property string entity_type
 * @property UuidInterface entity_id
 * @property string locale
 * @property string field
 * @property string value
 * @property Carbon|null created_at
 * @property Carbon|null updated_at
 * @property Carbon|null deleted_at
 */
class Translation extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'translations';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'entity_type',
        'entity_id',
        'locale',
        'field',
        'value',
    ];
}
