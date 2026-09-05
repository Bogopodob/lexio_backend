<?php

namespace App\Modules\User\Infrastructure\Persistence\Eloquent;

use App\Modules\User\Domain\Entities\Friendship;
use App\Modules\User\Domain\Entities\FriendWithProfile;
use App\Modules\User\Domain\Entities\LeaderboardRow;
use App\Modules\User\Domain\Ports\FriendshipRepositoryInterface;
use App\Modules\User\Infrastructure\Persistence\Eloquent\Models\Friendship as FriendshipModel;
use App\Modules\User\Infrastructure\Persistence\Eloquent\Models\User as UserModel;
use App\Modules\User\Infrastructure\Persistence\Eloquent\Models\UserProfile as UserProfileModel;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

final class EloquentFriendshipRepository implements FriendshipRepositoryInterface
{
    public function find(string $id): ?Friendship
    {
        $model = FriendshipModel::query()->find($id);

        return $model ? $this->toDomain($model) : null;
    }

    public function findBetween(string $userA, string $userB): ?Friendship
    {
        $model = FriendshipModel::query()
            ->where(function ($q) use ($userA, $userB) {
                $q->where('requester_id', $userA)->where('addressee_id', $userB);
            })
            ->orWhere(function ($q) use ($userA, $userB) {
                $q->where('requester_id', $userB)->where('addressee_id', $userA);
            })
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findPendingReverse(string $requesterId, string $addresseeId): ?Friendship
    {
        $model = FriendshipModel::query()
            ->where('requester_id', $addresseeId)
            ->where('addressee_id', $requesterId)
            ->where('status', 'pending')
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function save(Friendship $friendship): Friendship
    {
        $model = FriendshipModel::query()->updateOrCreate(
            ['id' => $friendship->id !== '' ? $friendship->id : Uuid::uuid4()->toString()],
            [
                'requester_id' => $friendship->requesterId,
                'addressee_id' => $friendship->addresseeId,
                'status' => $friendship->status,
            ],
        );

        return $this->toDomain($model);
    }

    public function delete(string $id): void
    {
        FriendshipModel::query()->where('id', $id)->delete();
    }

    public function pendingIncoming(string $userId): array
    {
        return FriendshipModel::query()
            ->where('addressee_id', $userId)
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (FriendshipModel $m) => $this->toDomain($m))
            ->all();
    }

    public function pendingOutgoing(string $userId): array
    {
        return FriendshipModel::query()
            ->where('requester_id', $userId)
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (FriendshipModel $m) => $this->toDomain($m))
            ->all();
    }

    public function friendsOf(string $userId): array
    {
        $rows = FriendshipModel::query()
            ->where('status', 'accepted')
            ->where(function ($q) use ($userId) {
                $q->where('requester_id', $userId)->orWhere('addressee_id', $userId);
            })
            ->orderBy('updated_at', 'desc')
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $friendIds = $rows->map(
            fn (FriendshipModel $m) => (string) $m->requester_id === $userId
                ? (string) $m->addressee_id
                : (string) $m->requester_id
        )->values()->all();

        $users = UserModel::query()->whereIn('id', $friendIds)->get()->keyBy(fn ($u) => (string) $u->id);
        $profiles = UserProfileModel::query()->whereIn('user_id', $friendIds)->get()->keyBy(fn ($p) => (string) $p->user_id);

        // Learning-module read models (infrastructure-level join, see DB_SCHEMA.md).
        $langProfiles = DB::table('user_language_profiles')
            ->whereIn('user_id', $friendIds)
            ->where('is_active', true)
            ->get()
            ->keyBy(fn ($r) => (string) $r->user_id);
        $stats = DB::table('user_language_stats')
            ->whereIn('profile_id', $langProfiles->pluck('id')->all())
            ->get()
            ->keyBy(fn ($r) => (string) $r->profile_id);

        $result = [];

        foreach ($rows as $row) {
            $friendId = (string) $row->requester_id === $userId
                ? (string) $row->addressee_id
                : (string) $row->requester_id;

            $user = $users->get($friendId);

            if (! $user) {
                continue;
            }

            $profile = $profiles->get($friendId);
            $langProfile = $langProfiles->get($friendId);
            $stat = $langProfile ? $stats->get((string) $langProfile->id) : null;

            $name = ($profile && $profile->name) ? $profile->name : $user->name;

            $result[] = new FriendWithProfile(
                userId: $friendId,
                name: $name,
                avatar: $profile ? $profile->avatar : null,
                level: $langProfile ? $langProfile->level : null,
                streakDays: $stat ? (int) $stat->streak_days : 0,
                friendshipId: (string) $row->id,
            );
        }

        return $result;
    }

    public function leaderboard(string $userId): array
    {
        $friendIds = FriendshipModel::query()
            ->where('status', 'accepted')
            ->where(function ($q) use ($userId) {
                $q->where('requester_id', $userId)->orWhere('addressee_id', $userId);
            })
            ->get()
            ->map(fn (FriendshipModel $m) => (string) $m->requester_id === $userId
                ? (string) $m->addressee_id
                : (string) $m->requester_id)
            ->values()
            ->all();

        $ids = array_values(array_unique([$userId, ...$friendIds]));

        $users = UserModel::query()->whereIn('id', $ids)->get()->keyBy(fn ($u) => (string) $u->id);
        $profiles = UserProfileModel::query()->whereIn('user_id', $ids)->get()->keyBy(fn ($p) => (string) $p->user_id);

        // Learning-module read models (infrastructure-level join, see DB_SCHEMA.md).
        $langProfiles = DB::table('user_language_profiles')
            ->whereIn('user_id', $ids)
            ->where('is_active', true)
            ->get()
            ->keyBy(fn ($r) => (string) $r->user_id);
        $stats = DB::table('user_language_stats')
            ->whereIn('profile_id', $langProfiles->pluck('id')->all())
            ->get()
            ->keyBy(fn ($r) => (string) $r->profile_id);

        $rows = [];

        foreach ($ids as $id) {
            $user = $users->get($id);

            if (! $user) {
                continue;
            }

            $profile = $profiles->get($id);
            $langProfile = $langProfiles->get($id);
            $stat = $langProfile ? $stats->get((string) $langProfile->id) : null;

            $rows[] = new LeaderboardRow(
                userId: $id,
                name: ($profile && $profile->name) ? $profile->name : $user->name,
                avatar: $profile ? $profile->avatar : null,
                level: $langProfile ? $langProfile->level : null,
                streakDays: $stat ? (int) $stat->streak_days : 0,
                isSelf: $id === $userId,
            );
        }

        usort($rows, fn (LeaderboardRow $a, LeaderboardRow $b) => $b->streakDays <=> $a->streakDays
            ?: strcmp((string) $a->name, (string) $b->name));

        return $rows;
    }

    public function searchUsers(string $excludeUserId, string $query, int $limit): array
    {
        $like = '%'.mb_strtolower(trim($query)).'%';

        return UserModel::query()
            ->where('id', '!=', $excludeUserId)
            ->where(function ($q) use ($like) {
                $q->whereRaw('LOWER(name) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(email) LIKE ?', [$like]);
            })
            ->limit(max(1, min(20, $limit)))
            ->get(['id', 'name', 'email'])
            ->map(fn ($u) => [
                'user_id' => (string) $u->id,
                'name' => $u->name,
                'email' => $u->email,
            ])
            ->all();
    }

    private function toDomain(FriendshipModel $m): Friendship
    {
        return new Friendship(
            id: (string) $m->id,
            requesterId: (string) $m->requester_id,
            addresseeId: (string) $m->addressee_id,
            status: $m->status,
        );
    }
}
