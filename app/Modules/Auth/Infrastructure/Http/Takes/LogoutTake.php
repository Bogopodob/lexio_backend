<?php

namespace App\Modules\Auth\Infrastructure\Http\Takes;

use App\Modules\Auth\Application\UseCases\Auth\Logout\LogoutCommand;
use App\Modules\Auth\Application\UseCases\Auth\Logout\LogoutUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class LogoutTake
{
    public function __construct(
        private LogoutUseCase $useCase,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $revoked = $this->useCase->handle(
            new LogoutCommand(token: (string) $request->bearerToken())
        );

        return response()->json([
            'success' => true,
            'data' => ['revoked' => $revoked],
        ]);
    }
}
