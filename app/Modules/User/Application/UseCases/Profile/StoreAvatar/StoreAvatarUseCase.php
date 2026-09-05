<?php

namespace App\Modules\User\Application\UseCases\Profile\StoreAvatar;

use App\Modules\User\Domain\Entities\UserProfile;
use App\Modules\User\Domain\Ports\UserProfileRepositoryInterface;
use App\Modules\User\Infrastructure\Security\AvatarInspector;
use App\Modules\User\Infrastructure\Security\InvalidImageException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

final readonly class StoreAvatarUseCase
{
    public function __construct(
        private UserProfileRepositoryInterface $profiles,
    ) {}

    /**
     * @return array{avatar_url: string}
     *
     * @throws InvalidImageException
     */
    public function handle(StoreAvatarCommand $command, UploadedFile $file): array
    {
        $info = AvatarInspector::inspect($file->getPathname());

        $profile = $this->profiles->findByUserId($command->userId);

        if (! $profile) {
            $profile = $this->profiles->save(new UserProfile(
                userId: $command->userId,
                name: null,
                lastname: null,
                surname: null,
                avatar: null,
            ));
        }

        $this->deletePrevious($command->userId);

        Storage::disk('local')->putFileAs(
            'avatars',
            $file,
            "{$command->userId}.{$info['extension']}"
        );

        $url = "/api/users/{$command->userId}/avatar?v=".Storage::disk('local')->lastModified(
            "avatars/{$command->userId}.{$info['extension']}"
        );

        $this->profiles->save(new UserProfile(
            userId: $profile->userId,
            name: $profile->name,
            lastname: $profile->lastname,
            surname: $profile->surname,
            avatar: $url,
            city: $profile->city,
            birthDate: $profile->birthDate,
            tags: $profile->tags,
            reminderSchedule: $profile->reminderSchedule,
            gender: $profile->gender,
        ));

        return ['avatar_url' => $url];
    }

    private function deletePrevious(string $userId): void
    {
        foreach (['jpg', 'jpeg', 'png'] as $ext) {
            $path = "avatars/{$userId}.{$ext}";

            if (Storage::disk('local')->exists($path)) {
                Storage::disk('local')->delete($path);
            }
        }
    }
}
