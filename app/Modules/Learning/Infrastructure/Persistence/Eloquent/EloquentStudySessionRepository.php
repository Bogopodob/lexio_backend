<?php

namespace App\Modules\Learning\Infrastructure\Persistence\Eloquent;

use App\Modules\Learning\Domain\Entities\StudyCard;
use App\Modules\Learning\Domain\Entities\StudySession;
use App\Modules\Learning\Domain\Entities\StudySessionItem;
use App\Modules\Learning\Domain\Entities\StudySessionItemRef;
use App\Modules\Learning\Domain\Ports\StudySessionRepositoryInterface;
use App\Modules\Learning\Infrastructure\Persistence\Database\Eloquent\Models\StudySession as SessionModel;
use App\Modules\Learning\Infrastructure\Persistence\Database\Eloquent\Models\StudySessionItem as ItemModel;
use App\Modules\Learning\Infrastructure\Persistence\Database\Eloquent\Models\UserLanguageProfile as ProfileModel;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

final class EloquentStudySessionRepository implements StudySessionRepositoryInterface
{
    public function createSession(
        string $userId,
        string $profileId,
        string $source,
        string $targetLanguageId,
        string $nativeLanguageId,
        ?string $categoryId = null,
    ): StudySession {
        $now = Carbon::now()->toDateTimeString();

        $model = SessionModel::query()->create([
            'id' => Uuid::uuid4()->toString(),
            'user_id' => $userId,
            'profile_id' => $profileId,
            'source' => $source,
            'category_id' => $categoryId,
            'status' => 'active',
            'total' => 0,
            'answered' => 0,
            'correct' => 0,
            'xp_earned' => 0,
            'started_at' => $now,
            'finished_at' => null,
        ]);

        return $this->toSession($model, []);
    }

    public function appendItems(string $sessionId, array $items): void
    {
        $position = ItemModel::query()->where('session_id', $sessionId)->max('position');
        $position = $position === null ? 0 : ((int) $position + 1);

        $rows = [];

        foreach (array_values($items) as $item) {
            $rows[] = [
                'id' => Uuid::uuid4()->toString(),
                'session_id' => $sessionId,
                'learnable_type' => $item['learnable_type'],
                'learnable_id' => $item['learnable_id'],
                'position' => $position++,
                'status' => 'pending',
                'quality' => null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];
        }

        if ($rows !== []) {
            ItemModel::query()->insert($rows);
        }

        SessionModel::query()->where('id', $sessionId)->update([
            'total' => ItemModel::query()->where('session_id', $sessionId)->count(),
        ]);
    }

    public function findSession(string $sessionId): ?StudySession
    {
        $model = SessionModel::query()->find($sessionId);

        if (! $model) {
            return null;
        }

        $items = ItemModel::query()
            ->where('session_id', $sessionId)
            ->orderBy('position')
            ->get()
            ->map(fn (ItemModel $m) => new StudySessionItem(
                id: (string) $m->id,
                learnableType: $m->learnable_type,
                learnableId: (string) $m->learnable_id,
                position: (int) $m->position,
                status: $m->status,
                quality: $m->quality !== null ? (int) $m->quality : null,
            ))
            ->all();

        return $this->toSession($model, $items);
    }

    public function listSessions(string $profileId, int $limit): array
    {
        return SessionModel::query()
            ->where('profile_id', $profileId)
            ->orderBy('created_at', 'desc')
            ->limit(max(1, min(50, $limit)))
            ->get()
            ->map(fn (SessionModel $m) => $this->toSession($m, []))
            ->all();
    }

    public function findActiveSession(string $profileId, ?string $categoryId = null): ?StudySession
    {
        $query = SessionModel::query()
            ->where('profile_id', $profileId)
            ->where('status', 'active');

        if ($categoryId === null) {
            $query->whereNull('category_id');
        } else {
            $query->where('category_id', $categoryId);
        }

        $model = $query->orderBy('created_at', 'desc')->first();

        return $model ? $this->findSession((string) $model->id) : null;
    }

    public function abandonActive(string $profileId, ?string $categoryId): int
    {
        $query = SessionModel::query()
            ->where('profile_id', $profileId)
            ->where('status', 'active');

        if ($categoryId === null) {
            $query->whereNull('category_id');
        } else {
            $query->where('category_id', $categoryId);
        }

        return $query->update(['status' => 'abandoned', 'updated_at' => Carbon::now()]);
    }

    public function saveSession(StudySession $session): StudySession
    {
        SessionModel::query()->where('id', $session->id)->update([
            'status' => $session->status,
            'total' => $session->total,
            'answered' => $session->answered,
            'correct' => $session->correct,
            'xp_earned' => $session->xpEarned,
            'finished_at' => $session->finishedAt,
        ]);

        return $this->findSession($session->id) ?? $session;
    }

    public function markItemAnswered(string $sessionId, string $learnableId, string $status, int $quality): void
    {
        ItemModel::query()
            ->where('session_id', $sessionId)
            ->where('learnable_id', $learnableId)
            ->where('status', 'pending')
            ->orderBy('position')
            ->limit(1)
            ->update(['status' => $status, 'quality' => $quality]);
    }

    public function nextPendingItem(string $sessionId): ?StudySessionItemRef
    {
        $model = ItemModel::query()
            ->where('session_id', $sessionId)
            ->where('status', 'pending')
            ->orderBy('position')
            ->first();

        return $model ? new StudySessionItemRef(
            id: (string) $model->id,
            learnableType: $model->learnable_type,
            learnableId: (string) $model->learnable_id,
            position: (int) $model->position,
        ) : null;
    }

    public function countByStatus(string $sessionId, string $status): int
    {
        return ItemModel::query()
            ->where('session_id', $sessionId)
            ->where('status', $status)
            ->count();
    }

    public function recentSessions(string $profileId, int $days): array
    {
        $days = max(1, min(31, $days));

        return SessionModel::query()
            ->where('profile_id', $profileId)
            ->where('started_at', '>=', Carbon::now()->subDays($days - 1)->startOfDay())
            ->orderBy('started_at', 'desc')
            ->limit(500)
            ->get(['started_at', 'finished_at', 'answered', 'xp_earned'])
            ->map(fn (SessionModel $m) => [
                'started_at' => $m->started_at ? (string) $m->started_at : null,
                'finished_at' => $m->finished_at ? (string) $m->finished_at : null,
                'answered' => (int) $m->answered,
                'xp_earned' => (int) $m->xp_earned,
            ])
            ->all();
    }

    public function cardFor(
        string $profileId,
        string $learnableType,
        string $learnableId,
        ?string $categoryId = null,
    ): ?StudyCard {
        $profile = ProfileModel::query()->find($profileId);

        if (! $profile) {
            return null;
        }

        $target = (string) $profile->target_language_id;
        $native = (string) $profile->native_language_id;
        $verbsOnly = $this->isVerbsCategory($categoryId);

        return match ($learnableType) {
            'entry' => $this->entryCard($learnableId, $target, $native, $verbsOnly),
            'phrase' => $this->phraseCard($learnableId, $target, $native),
            'user_entry' => $this->userEntryCard($learnableId, $target, $native),
            'user_phrase' => $this->userPhraseCard($learnableId, $target, $native),
            default => null,
        };

        if (! $card) {
            return null;
        }

        $ownHint = DB::table('word_hints')
            ->where('profile_id', $profileId)
            ->where('learnable_type', $learnableType)
            ->where('learnable_id', $learnableId)
            ->value('hint');

        $forms = [];
        $formsPattern = null;

        if ($learnableType === 'entry') {
            $formsPattern = DB::table('entries')
                ->where('id', $learnableId)
                ->value('forms_pattern');

            $forms = DB::table('word_forms')
                ->join(
                    'entry_translations',
                    'entry_translations.id',
                    '=',
                    'word_forms.entry_translation_id'
                )
                ->where('entry_translations.entry_id', $learnableId)
                ->where('entry_translations.language_id', $target)
                ->orderBy('word_forms.form_type')
                ->get(['word_forms.form', 'word_forms.form_type', 'word_forms.transcription'])
                ->map(fn ($row) => [
                    'form' => (string) $row->form,
                    'form_type' => (string) $row->form_type,
                    'transcription' => $row->transcription !== null ? (string) $row->transcription : null,
                ])
                ->all();
        }

        if ($ownHint === null && $forms === [] && $formsPattern === null) {
            return $card;
        }

        return new StudyCard(
            learnableType: $card->learnableType,
            learnableId: $card->learnableId,
            frontText: $card->frontText,
            frontTranscription: $card->frontTranscription,
            backTexts: $card->backTexts,
            hint: $card->hint,
            targetTexts: $card->targetTexts,
            nativeTexts: $card->nativeTexts,
            ownHint: $ownHint !== null ? (string) $ownHint : null,
            forms: $forms,
            formsPattern: $formsPattern !== null ? (string) $formsPattern : null,
        );
    }

    public function findNewEntries(string $profileId, ?string $categoryId, ?string $level, int $limit, int $offset = 0): array
    {
        $query = DB::table('entries')
            ->leftJoin('user_progresses', function ($join) use ($profileId) {
                $join->on('user_progresses.learnable_id', '=', 'entries.id')
                    ->where('user_progresses.profile_id', '=', $profileId)
                    ->where('user_progresses.learnable_type', '=', 'entry');
            })
            ->whereNull('user_progresses.id')
            ->when($level !== null, fn ($q) => $q->where('entries.level', $level))
            ->when($categoryId !== null, function ($q) use ($categoryId) {
                $q->join('entry_category', 'entry_category.entry_id', '=', 'entries.id')
                    ->where('entry_category.category_id', $categoryId);
            })
            ->orderByRaw('entries.frequency_rank ASC NULLS LAST')
            ->limit(max(1, min(100, $limit)))
            ->offset(max(0, $offset))
            ->select('entries.id');

        return $query->get()
            ->map(fn ($r) => ['learnable_type' => 'entry', 'learnable_id' => (string) $r->id])
            ->all();
    }

    public function countNewEntries(string $profileId, ?string $categoryId, ?string $level): int
    {
        return DB::table('entries')
            ->leftJoin('user_progresses', function ($join) use ($profileId) {
                $join->on('user_progresses.learnable_id', '=', 'entries.id')
                    ->where('user_progresses.profile_id', '=', $profileId)
                    ->where('user_progresses.learnable_type', '=', 'entry');
            })
            ->whereNull('user_progresses.id')
            ->when($level !== null, fn ($q) => $q->where('entries.level', $level))
            ->when($categoryId !== null, function ($q) use ($categoryId) {
                $q->join('entry_category', 'entry_category.entry_id', '=', 'entries.id')
                    ->where('entry_category.category_id', $categoryId);
            })
            ->count();
    }

    private function isVerbsCategory(?string $categoryId): bool
    {
        if ($categoryId === null) {
            return false;
        }

        return DB::table('categories')->where('id', $categoryId)->value('type') === 'verbs';
    }

    private function entryCard(string $entryId, string $target, string $native, bool $verbsOnly = false): ?StudyCard
    {
        $rows = DB::table('entry_translations')
            ->where('entry_id', $entryId)
            ->whereIn('language_id', [$target, $native])
            ->orderBy('created_at')
            ->get(['language_id', 'text', 'transcription', 'part_of_speech']);

        if ($verbsOnly) {
            $verbRows = $rows->filter(
                fn ($row) => ($row->part_of_speech ?? null) === 'verb'
            );
            $hasTarget = $verbRows->contains(fn ($row) => (string) $row->language_id === $target);
            $hasNative = $verbRows->contains(fn ($row) => (string) $row->language_id !== $target);

            // Never break a card: fall back to all translations if a side is missing.
            if ($hasTarget && $hasNative) {
                $rows = $verbRows;
            }
        }

        return $this->buildCard('entry', $entryId, $rows, $target);
    }

    private function phraseCard(string $phraseId, string $target, string $native): ?StudyCard
    {
        $rows = DB::table('phrase_translations')
            ->where('phrase_id', $phraseId)
            ->whereIn('language_id', [$target, $native])
            ->orderBy('created_at')
            ->get(['language_id', 'text', 'transcription']);

        return $this->buildCard('phrase', $phraseId, $rows, $target, false);
    }

    private function userEntryCard(string $entryId, string $target, string $native): ?StudyCard
    {
        $rows = DB::table('user_entry_translations')
            ->where('user_entry_id', $entryId)
            ->whereIn('language_id', [$target, $native])
            ->orderBy('created_at')
            ->get(['language_id', 'text', 'transcription', 'part_of_speech']);

        return $this->buildCard('user_entry', $entryId, $rows, $target);
    }

    private function userPhraseCard(string $phraseId, string $target, string $native): ?StudyCard
    {
        $rows = DB::table('user_phrase_translations')
            ->where('user_phrase_id', $phraseId)
            ->whereIn('language_id', [$target, $native])
            ->orderBy('created_at')
            ->get(['language_id', 'text', 'transcription']);

        return $this->buildCard('user_phrase', $phraseId, $rows, $target, false);
    }

    /**
     * @param  Collection<int, object>  $rows
     */
    private function buildCard(string $type, string $id, $rows, string $target, bool $withPos = true): ?StudyCard
    {
        $front = null;
        $back = [];
        $hint = null;
        $targetTexts = [];
        $nativeTexts = [];

        foreach ($rows as $row) {
            $lang = (string) $row->language_id;
            $text = (string) $row->text;

            if ($lang === $target) {
                $targetTexts[] = $text;

                if ($front === null) {
                    $front = ['text' => $text, 'tr' => $row->transcription ?? null];

                    if ($withPos && isset($row->part_of_speech) && $row->part_of_speech) {
                        $hint = (string) $row->part_of_speech;
                    }
                }
            } else {
                $back[] = $text;
                $nativeTexts[] = $text;
            }
        }

        if ($front === null) {
            return null;
        }

        return new StudyCard(
            learnableType: $type,
            learnableId: $id,
            frontText: (string) $front['text'],
            frontTranscription: $front['tr'],
            backTexts: $this->uniqueTexts($back),
            hint: $hint,
            targetTexts: $this->uniqueTexts($targetTexts),
            nativeTexts: $this->uniqueTexts($nativeTexts),
        );
    }

    /**
     * Case-insensitive dedupe, first occurrence wins.
     *
     * @param  list<string>  $texts
     * @return list<string>
     */
    private function uniqueTexts(array $texts): array
    {
        $seen = [];
        $out = [];

        foreach ($texts as $text) {
            $key = mb_strtolower(trim($text));

            if ($key === '' || isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $out[] = $text;
        }

        return $out;
    }

    public function distractors(
        string $profileId,
        string $learnableType,
        string $learnableId,
        string $side,
        ?string $categoryId,
        int $count,
    ): array {
        $count = max(1, min(6, $count));

        $profile = ProfileModel::query()->find($profileId);

        if (! $profile) {
            return [];
        }

        $languageId = $side === 'target'
            ? (string) $profile->target_language_id
            : (string) $profile->native_language_id;
        $userId = (string) $profile->user_id;

        $card = $this->cardFor($profileId, $learnableType, $learnableId);
        $sideTexts = $side === 'target' ? ($card?->targetTexts ?? []) : ($card?->nativeTexts ?? []);
        $exclude = [];

        foreach ($sideTexts as $text) {
            $exclude[mb_strtolower(trim((string) $text))] = true;
        }

        $tables = [
            ['type' => 'entry', 'table' => 'entry_translations', 'fk' => 'entry_id', 'user' => false],
            ['type' => 'phrase', 'table' => 'phrase_translations', 'fk' => 'phrase_id', 'user' => false],
            ['type' => 'user_entry', 'table' => 'user_entry_translations', 'fk' => 'user_entry_id', 'user' => true, 'parent' => 'user_entries', 'parent_fk' => 'user_entry_id'],
            ['type' => 'user_phrase', 'table' => 'user_phrase_translations', 'fk' => 'user_phrase_id', 'user' => true, 'parent' => 'user_phrases', 'parent_fk' => 'user_phrase_id'],
        ];

        usort($tables, fn ($a, $b) => ($a['type'] === $learnableType ? 0 : 1) <=> ($b['type'] === $learnableType ? 0 : 1));

        $picked = [];
        $seen = $exclude;

        $collect = function (array $cfg, bool $sameCategory) use (
            $languageId, $userId, $learnableType, $learnableId, $categoryId, $count, &$picked, &$seen,
        ): void {
            if (count($picked) >= $count) {
                return;
            }

            if ($sameCategory && ($cfg['type'] !== 'entry' || $categoryId === null)) {
                return;
            }

            $query = DB::table($cfg['table'].' as t')
                ->where('t.language_id', $languageId)
                ->inRandomOrder()
                ->limit($count * 5)
                ->select('t.text');

            if ($cfg['type'] === $learnableType) {
                $query->where("t.{$cfg['fk']}", '!=', $learnableId);
            }

            if ($cfg['user']) {
                $parent = $cfg['parent'];
                $parentFk = $cfg['parent_fk'];
                $query->join("$parent as p", 'p.id', '=', "t.$parentFk")
                    ->where('p.user_id', $userId);
            }

            if ($sameCategory) {
                $query->join('entry_category as ec', 'ec.entry_id', '=', 't.entry_id')
                    ->where('ec.category_id', $categoryId);
            }

            foreach ($query->get() as $row) {
                if (count($picked) >= $count) {
                    break;
                }

                $text = trim((string) $row->text);
                $key = mb_strtolower($text);

                if ($text === '' || isset($seen[$key])) {
                    continue;
                }

                $seen[$key] = true;
                $picked[] = $text;
            }
        };

        foreach ($tables as $cfg) {
            $collect($cfg, true);
        }

        foreach ($tables as $cfg) {
            $collect($cfg, false);
        }

        return array_values($picked);
    }

    /**
     * @param  list<StudySessionItem>  $items
     */
    private function toSession(SessionModel $m, array $items): StudySession
    {
        return new StudySession(
            id: (string) $m->id,
            userId: (string) $m->user_id,
            profileId: (string) $m->profile_id,
            source: $m->source,
            status: $m->status,
            total: (int) $m->total,
            answered: (int) $m->answered,
            correct: (int) $m->correct,
            xpEarned: (int) $m->xp_earned,
            startedAt: $m->started_at ? (string) $m->started_at : null,
            finishedAt: $m->finished_at ? (string) $m->finished_at : null,
            categoryId: $m->category_id ? (string) $m->category_id : null,
            items: $items,
        );
    }
}
