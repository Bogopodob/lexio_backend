<?php

namespace App\Modules\Library\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class LibraryMedia extends Model
{
    use HasUuids;

    protected $table = 'library_media';

    protected $primaryKey = 'id';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'user_id',
        'kind',
        'path',
        'mime',
        'bytes',
        'created_at',
        'updated_at',
    ];
}
