<?php

namespace App\Modules\Library\Infrastructure\Http\Takes;

use App\Modules\Library\Application\UseCases\UploadMedia\UploadMediaCommand;
use App\Modules\Library\Application\UseCases\UploadMedia\UploadMediaUseCase;
use App\Modules\Library\Infrastructure\Http\Requests\UploadMediaRequest;
use App\Modules\Library\Infrastructure\Http\Resources\LibraryResponseResource;
use App\Modules\Library\Infrastructure\Security\InvalidMediaException;
use Illuminate\Http\JsonResponse;

final readonly class UploadMediaTake
{
    private const ERROR_KEYS = [
        'UNREADABLE' => 'api.media.unreadable',
        'EMPTY' => 'api.media.empty',
        'TOO_BIG' => 'api.media.too_big',
        'NOT_IMAGE' => 'api.media.not_image',
        'NOT_AUDIO' => 'api.media.not_audio',
        'BAD_SIZE' => 'api.media.bad_size',
        'TRAILING_DATA' => 'api.media.trailing_data',
        'TRUNCATED' => 'api.media.truncated',
    ];

    public function __construct(
        private UploadMediaUseCase $useCase,
    ) {}

    public function handle(string $userId, UploadMediaRequest $request): JsonResponse
    {
        $file = $request->file('file');

        if (! $file || ! $file->isValid()) {
            return response()->json(['success' => false, 'message' => __('api.media.upload_failed')], 422);
        }

        try {
            $media = $this->useCase->handle(
                new UploadMediaCommand($userId, (string) $request->validated('kind')),
                $file,
            );
        } catch (InvalidMediaException $e) {
            $key = self::ERROR_KEYS[$e->getMessage()] ?? 'api.media.upload_failed';

            return response()->json(['success' => false, 'message' => __($key)], 422);
        }

        return LibraryResponseResource::make([
            'id' => $media->id,
            'kind' => $media->kind,
            'mime' => $media->mime,
            'bytes' => $media->bytes,
        ])->response()->setStatusCode(201);
    }
}
