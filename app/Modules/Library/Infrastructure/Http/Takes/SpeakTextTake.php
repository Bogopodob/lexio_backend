<?php

namespace App\Modules\Library\Infrastructure\Http\Takes;

use App\Modules\Library\Application\UseCases\SpeakText\SpeakTextCommand;
use App\Modules\Library\Application\UseCases\SpeakText\SpeakTextUseCase;
use App\Modules\Library\Application\UseCases\TranscribeText\TranscribeTextCommand;
use App\Modules\Library\Application\UseCases\TranscribeText\TranscribeTextUseCase;
use App\Modules\Library\Infrastructure\Http\Requests\SpeakTextRequest;
use App\Modules\Library\Infrastructure\Http\Resources\LibraryResponseResource;
use Illuminate\Http\JsonResponse;

final readonly class SpeakTextTake
{
    public function __construct(
        private SpeakTextUseCase $speak,
        private TranscribeTextUseCase $transcribe,
    ) {}

    public function speak(string $userId, SpeakTextRequest $request): JsonResponse
    {
        $result = $this->speak->handle(new SpeakTextCommand(
            userId: $userId,
            text: (string) $request->validated('text'),
            lang: (string) ($request->validated('lang') ?? 'en'),
        ));

        if (! $result) {
            return response()->json(['success' => false, 'message' => __('api.media.tts_failed')], 422);
        }

        return LibraryResponseResource::make([
            'media_id' => $result->media->id,
            'mime' => $result->media->mime,
            'transcription' => $result->transcription,
        ])->response()->setStatusCode(201);
    }

    public function transcribe(SpeakTextRequest $request): JsonResponse
    {
        $transcription = $this->transcribe->handle(new TranscribeTextCommand(
            text: (string) $request->validated('text'),
            lang: (string) ($request->validated('lang') ?? 'en'),
        ));

        if ($transcription === null) {
            return response()->json(['success' => false, 'message' => __('api.media.tts_failed')], 422);
        }

        return LibraryResponseResource::make(['transcription' => $transcription])->response();
    }
}
