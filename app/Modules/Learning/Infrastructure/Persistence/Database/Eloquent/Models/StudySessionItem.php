<?php

namespace App\Modules\Learning\Infrastructure\Persistence\Database\Eloquent\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\UuidInterface;

/**
 * @property UuidInterface id
 * @property UuidInterface session_id
 * @property string learnable_type
 * @property UuidInterface learnable_id
 * @property int position
 * @property string status
 * @property ?int quality
 * @property ?Carbon created_at
 * @property ?Carbon updated_at
 */
final class StudySessionItem extends Model
{
    use HasUuids;

    protected $table = 'study_session_items';

    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'session_id',
        'learnable_type',
        'learnable_id',
        'position',
        'status',
        'quality',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'quality' => 'integer',
        ];
    }
}
