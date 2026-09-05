<?php

namespace App\Modules\Learning\Infrastructure\Persistence\Database\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * User's own mnemonic hint for a learnable, scoped to a language profile.
 */
final class WordHint extends Model
{
    use HasUuids;

    protected $table = 'word_hints';

    protected $primaryKey = 'id';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'profile_id',
        'learnable_type',
        'learnable_id',
        'hint',
        'created_at',
        'updated_at',
    ];
}
