<?php

namespace App\Modules\Catalog\Infrastructure\Http\Takes;

use App\Modules\Catalog\Application\UseCases\GetEntry\GetEntryCommand;
use App\Modules\Catalog\Application\UseCases\GetEntry\GetEntryUseCase;
use App\Modules\Catalog\Infrastructure\Http\Resources\CatalogResponseResource;
use App\Modules\Catalog\Infrastructure\Http\Resources\EntryDetailsResource;
use Illuminate\Http\JsonResponse;

final readonly class GetEntryTake
{
    public function __construct(
        private GetEntryUseCase $useCase,
    ) {}

    public function handle(string $entryId): JsonResponse
    {
        $result = $this->useCase->handle(new GetEntryCommand($entryId));

        if (! $result) {
            return response()->json(['success' => false, 'message' => __('api.entry.not_found')], 404);
        }

        $data = EntryDetailsResource::make($result)->resolve(request());

        return CatalogResponseResource::make($data)->response();
    }
}
