<?php

namespace App\Modules\Learning\Infrastructure\Persistence\Eloquent;

use App\Modules\Learning\Domain\Ports\WordHintRepositoryInterface;
use App\Modules\Learning\Infrastructure\Persistence\Database\Eloquent\Models\WordHint as HintModel;

final class EloquentWordHintRepository implements WordHintRepositoryInterface
{
    public function find(string $profileId, string $learnableType, string $learnableId): ?string
    {
        $model = HintModel::query()
            ->where('profile_id', $profileId)
            ->where('learnable_type', $learnableType)
            ->where('learnable_id', $learnableId)
            ->first();

        return $model ? (string) $model->hint : null;
    }

    public function save(string $profileId, string $learnableType, string $learnableId, string $hint): ?string
    {
        $hint = trim($hint);

        if ($hint === '') {
            HintModel::query()
                ->where('profile_id', $profileId)
                ->where('learnable_type', $learnableType)
                ->where('learnable_id', $learnableId)
                ->delete();

            return null;
        }

        $model = HintModel::query()->firstOrNew([
            'profile_id' => $profileId,
            'learnable_type' => $learnableType,
            'learnable_id' => $learnableId,
        ]);

        $model->hint = mb_substr($hint, 0, 280);
        $model->save();

        return (string) $model->hint;
    }
}
