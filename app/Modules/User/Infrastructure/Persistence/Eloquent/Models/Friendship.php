<?php

namespace App\Modules\User\Infrastructure\Persistence\Eloquent\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\UuidInterface;

/**
 * @property UuidInterface id
 * @property UuidInterface requester_id
 * @property UuidInterface addressee_id
 * @property string status
 * @property ?Carbon created_at
 * @property ?Carbon updated_at
 */
final class Friendship extends Model
{
    use HasUuids;

    protected $table = 'friendships';

    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'requester_id',
        'addressee_id',
        'status',
        'created_at',
        'updated_at',
    ];
}
