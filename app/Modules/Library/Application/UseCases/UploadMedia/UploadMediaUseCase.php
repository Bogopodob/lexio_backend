<?php

namespace App\Modules\Library\Application\UseCases\UploadMedia;

use App\Modules\Library\Domain\Entities\LibraryMedia;
use App\Modules\Library\Domain\Ports\LibraryRepositoryInterface;
use App\Modules\Library\Infrastructure\Security\InvalidMediaException;
use App\Modules\Library\Infrastructure\Security\MediaInspector;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Ramsey\Uuid\Uuid;

final readonly class UploadMediaUseCase
{
    public function __construct(
        private LibraryRepositoryInterface $library,
    ) {}

    /**
     * @throws InvalidMediaException
     */
    public function handle(UploadMediaCommand $command, UploadedFile $file): LibraryMedia
    {
        $info = $command->kind === 'audio'
            ? MediaInspector::inspectAudio($file->getPathname())
            : MediaInspector::inspectImage($file->getPathname());

        $path = "library/media/{$command->userId}/".Uuid::uuid4()->toString().".{$info['extension']}";

        Storage::disk('local')->putFileAs(
            "library/media/{$command->userId}",
            $file,
            basename($path)
        );

        return $this->library->saveMedia(
            $command->userId,
            $command->kind,
            $path,
            $info['mime'],
            (int) $file->getSize(),
        );
    }
}
