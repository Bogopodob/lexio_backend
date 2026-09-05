<?php

namespace App\Modules\Learning\Infrastructure\Http\Resources;

use App\Modules\Learning\Domain\Entities\StudyCard;
use Illuminate\Http\Resources\Json\JsonResource;

final class StudyCardResource extends JsonResource
{
    public function __construct(private readonly StudyCard $card)
    {
        parent::__construct($card);
    }

    public function toArray($request): array
    {
        return [
            'learnable_type' => $this->card->learnableType,
            'learnable_id' => $this->card->learnableId,
            'front_text' => $this->card->frontText,
            'front_transcription' => $this->card->frontTranscription,
            'back_texts' => $this->card->backTexts,
            'hint' => $this->card->hint,
            'target_texts' => $this->card->targetTexts,
            'native_texts' => $this->card->nativeTexts,
        ];
    }
}
