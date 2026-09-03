<?php

namespace App\Modules\Library\Infrastructure\Http\Resources;

use App\Modules\Library\Domain\Entities\UserEntry;
use Illuminate\Http\Resources\Json\JsonResource;

final class UserEntryResource extends JsonResource
{
    public function __construct(private readonly UserEntry $entry)
    {
        parent::__construct($entry);
    }

    public function toArray($request): array
    {
        return [
            'id' => $this->entry->id,
            'category_id' => $this->entry->categoryId,
            'image_path' => $this->entry->imagePath,
            'translations' => array_map(fn ($t) => [
                'id' => $t->id,
                'language_id' => $t->languageId,
                'text' => $t->text,
                'transcription' => $t->transcription,
                'part_of_speech' => $t->partOfSpeech,
                'notes' => $t->notes,
            ], $this->entry->translations),
        ];
    }
}
