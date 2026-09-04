<?php

namespace App\Modules\User\Application\UseCases\Profile\UpsertUserProfile;

use App\Modules\User\Domain\Entities\UserProfile;
use App\Modules\User\Domain\Ports\UserProfileRepositoryInterface;

final readonly class UpsertUserProfileUseCase
{
    public function __construct(
        private UserProfileRepositoryInterface $userProfileRepository,
    ) {}

    private const WEEKDAYS = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

    /**
     * Keep only known weekdays, valid HH:MM times, max 3 per day, sorted.
     *
     * @param  array<string, mixed>  $schedule
     * @return array<string, list<string>>
     */
    private static function normalizeSchedule(array $schedule): array
    {
        $result = [];

        foreach (self::WEEKDAYS as $day) {
            if (! isset($schedule[$day]) || ! is_array($schedule[$day])) {
                continue;
            }

            $times = [];

            foreach ($schedule[$day] as $time) {
                if (! is_string($time)) {
                    continue;
                }

                $time = trim($time);

                if (preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time) && ! in_array($time, $times, true)) {
                    $times[] = $time;
                }

                if (count($times) >= 3) {
                    break;
                }
            }

            if ($times !== []) {
                sort($times);
                $result[$day] = $times;
            }
        }

        return $result;
    }

    public function handle(UpsertUserProfileCommand $command): UserProfile
    {
        return $this->userProfileRepository->save(
            new UserProfile(
                userId: $command->userId,
                name: $command->name,
                lastname: $command->lastname,
                surname: $command->surname,
                avatar: $command->avatar,
                city: $command->city,
                birthDate: $command->birthDate,
                tags: $command->tags,
                reminderSchedule: $command->reminderSchedule === null
                    ? null
                    : self::normalizeSchedule($command->reminderSchedule),
            )
        );
    }
}
