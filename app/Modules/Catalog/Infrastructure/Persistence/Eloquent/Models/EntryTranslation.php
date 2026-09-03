<?php

namespace App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\UuidInterface;

/**
 * @property UuidInterface id
 * @property UuidInterface entry_id
 * @property UuidInterface meaning_id
 * @property UuidInterface language_id
 * @property string text
 * @property ?string transcription
 * @property ?string audio_path
 * @property ?string part_of_speech
 * @property ?Carbon created_at
 * @property ?Carbon updated_at
 */
final class EntryTranslation extends Model
{
    use HasUuids;

    protected $table = 'entry_translations';

    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'entry_id',
        'meaning_id',
        'language_id',
        'text',
        'transcription',
        'audio_path',
        'part_of_speech',
        'created_at',
        'updated_at',
    ];
}
