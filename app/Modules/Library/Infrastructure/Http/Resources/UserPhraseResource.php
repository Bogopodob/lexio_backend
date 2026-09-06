<?php

namespace App\Modules\Library\Infrastructure\Http\Resources;

use App\Modules\Library\Domain\Entities\UserPhrase;
use Illuminate\Http\Resources\Json\JsonResource;

final class UserPhraseResource extends JsonResource
{
    public function __construct(private readonly UserPhrase $phrase)
    {
        parent::__construct($phrase);
    }

    public function toArray($request): array
    {
        return [
            'id' => $this->phrase->id,
            'category_id' => $this->phrase->categoryId,
            'image_path' => $this->phrase->imagePath,
            'phrase_type' => $this->phrase->phraseType,
            'translations' => array_map(fn ($t) => [
                'id' => $t->id,
                'language_id' => $t->languageId,
                'text' => $t->text,
                'transcription' => $t->transcription,
                'notes' => $t->notes,
                'audio_path' => $t->audioPath,
            ], $this->phrase->translations),
        ];
    }
}
