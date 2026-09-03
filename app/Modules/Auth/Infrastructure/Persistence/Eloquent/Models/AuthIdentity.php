<?php

namespace App\Modules\Auth\Infrastructure\Persistence\Eloquent\Models;

use App\Modules\Auth\Domain\Enums\AuthProviderEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\UuidInterface;

/**
 * @property UuidInterface id
 * @property UuidInterface user_id
 * @property AuthProviderEnum $provider
 * @property string provider_id
 * @property ?string secret
 * @property ?string access_token
 * @property ?string refresh_token
 * @property ?Carbon token_expires_at
 * @property ?Carbon verified_at
 * @property ?Carbon last_used_at
 * @property ?Carbon created_at
 * @property ?Carbon updated_at
 */
class AuthIdentity extends Model
{
    use HasUuids;

    protected $table = 'auth_identities';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'user_id',
        'provider',
        'provider_id',
        'secret',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'verified_at',
        'last_used_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
        'verified_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];
}
