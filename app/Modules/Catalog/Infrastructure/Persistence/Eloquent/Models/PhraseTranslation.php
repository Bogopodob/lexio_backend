<?php

namespace App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\UuidInterface;

/**
 * @property UuidInterface id
 * @property UuidInterface phrase_id
 * @property UuidInterface language_id
 * @property string text
 * @property ?string transcription
 * @property ?string audio_path
 * @property ?Carbon created_at
 * @property ?Carbon updated_at
 */
final class PhraseTranslation extends Model
{
    use HasUuids;

    protected $table = 'phrase_translations';

    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'phrase_id',
        'language_id',
        'text',
        'transcription',
        'audio_path',
        'created_at',
        'updated_at',
    ];
}
