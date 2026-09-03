<?php

namespace App\Modules\Catalog\Infrastructure\Http\Resources;

use App\Modules\Catalog\Domain\Entities\EntryDetails;
use Illuminate\Http\Resources\Json\JsonResource;

final class EntryDetailsResource extends JsonResource
{
    public function __construct(private readonly EntryDetails $details)
    {
        parent::__construct($details);
    }

    public function toArray($request): array
    {
        $d = $this->details;

        return [
            'id' => $d->entry->id,
            'level' => $d->entry->level,
            'image_path' => $d->entry->imagePath,
            'frequency_rank' => $d->entry->frequencyRank,
            'category_ids' => $d->categoryIds,
            'meanings' => array_map(fn ($m) => [
                'id' => $m->id,
                'note' => $m->note,
            ], $d->meanings),
            'translations' => array_map(fn ($t) => [
                'id' => $t->id,
                'meaning_id' => $t->meaningId,
                'language_id' => $t->languageId,
                'text' => $t->text,
                'transcription' => $t->transcription,
                'audio_path' => $t->audioPath,
                'part_of_speech' => $t->partOfSpeech,
            ], $d->translations),
            'forms' => array_map(fn ($f) => [
                'id' => $f->id,
                'translation_id' => $f->translationId,
                'form' => $f->form,
                'form_type' => $f->formType,
            ], $d->forms),
            'examples' => array_map(fn ($e) => [
                'id' => $e->phrase->id,
                'level' => $e->phrase->level,
                'phrase_type' => $e->phrase->phraseType,
                'image_path' => $e->phrase->imagePath,
                'meaning_id' => $e->meaningId,
                'translations' => array_map(fn ($t) => [
                    'id' => $t->id,
                    'language_id' => $t->languageId,
                    'text' => $t->text,
                    'transcription' => $t->transcription,
                    'audio_path' => $t->audioPath,
                ], $e->translations),
            ], $d->examples),
            'media' => array_map(fn ($m) => [
                'id' => $m->id,
                'entry_id' => $m->entryId,
                'translation_id' => $m->translationId,
                'type' => $m->type,
                'path' => $m->path,
                'source' => $m->source,
                'source_title' => $m->sourceTitle,
                'content_source_id' => $m->contentSourceId,
                'sort' => $m->sort,
            ], $d->media),
        ];
    }
}
