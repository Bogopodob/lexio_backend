<?php

namespace App\Modules\Auth\Infrastructure\Http\Takes;

use App\Modules\Auth\Application\UseCases\Auth\LoginByEmailOtp\RequestEmailCodeCommand;
use App\Modules\Auth\Application\UseCases\Auth\LoginByEmailOtp\RequestEmailCodeUseCase;
use App\Modules\Auth\Infrastructure\Http\Requests\RequestEmailCodeRequest;
use App\Modules\Auth\Infrastructure\Http\Resources\RequestEmailCodeResultResource;
use Illuminate\Http\JsonResponse;
use Random\RandomException;

final readonly class RequestEmailCodeTake
{
    public function __construct(
        private RequestEmailCodeUseCase $useCase,
    ) {}

    /**
     * @throws RandomException
     */
    public function handle(RequestEmailCodeRequest $request): JsonResponse
    {
        $result = $this->useCase->handle(
            new RequestEmailCodeCommand(
                email: (string) $request->string('email'),
                locale: $this->resolveLocale($request),
            )
        );

        return RequestEmailCodeResultResource::make($result)->response();
    }

    private function resolveLocale(RequestEmailCodeRequest $request): ?string
    {
        $rawLanguage = $request->header('Accept-Language', app()->getLocale());

        if (! is_string($rawLanguage) || trim($rawLanguage) === '') {
            return null;
        }

        $language = mb_strtolower(trim(explode(',', $rawLanguage)[0]));
        if ($language === '') {
            return null;
        }

        $parts = preg_split('/[-_]/', $language) ?: [];
        $base = isset($parts[0]) ? trim((string) $parts[0]) : '';

        return $base !== '' ? $base : null;
    }
}
