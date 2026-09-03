<?php

namespace App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\UuidInterface;

/**
 * @property UuidInterface id
 * @property UuidInterface phrase_id
 * @property UuidInterface category_id
 */
final class PhraseCategory extends Model
{
    use HasUuids;

    protected $table = 'phrases_categories';

    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'phrase_id',
        'category_id',
    ];
}
