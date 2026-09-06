<?php

namespace App\Modules\Library\Infrastructure\Http\Takes;

use App\Modules\Library\Domain\Ports\LibraryRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final readonly class StreamMediaTake
{
    public function __construct(
        private LibraryRepositoryInterface $library,
    ) {}

    public function handle(string $userId, string $mediaId): BinaryFileResponse|JsonResponse
    {
        $media = $this->library->findMedia($userId, $mediaId);

        if (! $media || ! Storage::disk('local')->exists($media->path)) {
            return response()->json(['success' => false, 'message' => __('api.media.not_found')], 404);
        }

        return response()->file(Storage::disk('local')->path($media->path), [
            'Content-Type' => $media->mime,
            'Content-Disposition' => 'inline',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
