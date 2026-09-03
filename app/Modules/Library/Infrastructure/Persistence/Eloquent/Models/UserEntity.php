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
 * @property ?string image_path
 * @property ?Carbon created_at
 * @property ?Carbon updated_at
 */
final class UserEntity extends Model
{
    use HasUuids;

    protected $table = 'user_entries';

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
        'image_path',
        'created_at',
        'updated_at',
    ];
}
