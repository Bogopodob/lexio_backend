<?php

namespace App\Modules\User\Infrastructure\Http\Takes;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final readonly class StreamAvatarTake
{
    private const MIME_BY_EXTENSION = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
    ];

    public function handle(string $userId): BinaryFileResponse|JsonResponse
    {
        foreach (array_keys(self::MIME_BY_EXTENSION) as $ext) {
            $path = "avatars/{$userId}.{$ext}";

            if (! Storage::disk('local')->exists($path)) {
                continue;
            }

            return response()->file(Storage::disk('local')->path($path), [
                'Content-Type' => self::MIME_BY_EXTENSION[$ext],
                'Content-Disposition' => 'inline; filename="avatar.'.$ext.'"',
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'public, max-age=86400',
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Not found'], 404);
    }
}
