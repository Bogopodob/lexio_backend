<?php

namespace App\Modules\Library\Infrastructure\Persistence\Eloquent\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\UuidInterface;

/**
 * @property UuidInterface id
 * @property UuidInterface user_phrase_id
 * @property UuidInterface language_id
 * @property string text
 * @property ?string transcription
 * @property ?string notes
 * @property ?Carbon created_at
 * @property ?Carbon updated_at
 */
final class UserPhraseTranslation extends Model
{
    use HasUuids;

    protected $table = 'user_phrase_translations';

    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'user_phrase_id',
        'language_id',
        'text',
        'transcription',
        'notes',
        'created_at',
        'updated_at',
    ];
}
