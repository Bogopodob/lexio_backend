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
    ): StudySession {
        $now = Carbon::now()->toDateTimeString();

        $model = SessionModel::query()->create([
            'id' => Uuid::uuid4()->toString(),
            'user_id' => $userId,
            'profile_id' => $profileId,
            'source' => $source,
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

    public function findActiveSession(string $profileId): ?StudySession
    {
        $model = SessionModel::query()
            ->where('profile_id', $profileId)
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->first();

        return $model ? $this->findSession((string) $model->id) : null;
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

    public function cardFor(string $profileId, string $learnableType, string $learnableId): ?StudyCard
    {
        $profile = ProfileModel::query()->find($profileId);

        if (! $profile) {
            return null;
        }

        $target = (string) $profile->target_language_id;
        $native = (string) $profile->native_language_id;

        return match ($learnableType) {
            'entry' => $this->entryCard($learnableId, $target, $native),
            'phrase' => $this->phraseCard($learnableId, $target, $native),
            'user_entry' => $this->userEntryCard($learnableId, $target, $native),
            'user_phrase' => $this->userPhraseCard($learnableId, $target, $native),
            default => null,
        };
    }

    public function findNewEntries(string $profileId, ?string $categoryId, ?string $level, int $limit): array
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
            ->select('entries.id');

        return $query->get()
            ->map(fn ($r) => ['learnable_type' => 'entry', 'learnable_id' => (string) $r->id])
            ->all();
    }

    private function entryCard(string $entryId, string $target, string $native): ?StudyCard
    {
        $rows = DB::table('entry_translations')
            ->where('entry_id', $entryId)
            ->whereIn('language_id', [$target, $native])
            ->orderBy('created_at')
            ->get(['language_id', 'text', 'transcription', 'part_of_speech']);

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

        foreach ($rows as $row) {
            $lang = (string) $row->language_id;

            if ($lang === $target && $front === null) {
                $front = ['text' => $row->text, 'tr' => $row->transcription ?? null];

                if ($withPos && isset($row->part_of_speech) && $row->part_of_speech) {
                    $hint = (string) $row->part_of_speech;
                }
            } elseif ($lang !== $target) {
                $back[] = (string) $row->text;
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
            backTexts: array_values(array_unique($back)),
            hint: $hint,
        );
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
            items: $items,
        );
    }
}
