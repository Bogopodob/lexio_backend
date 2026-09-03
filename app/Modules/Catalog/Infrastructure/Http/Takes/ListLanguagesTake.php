<?php

namespace App\Modules\Catalog\Infrastructure\Http\Takes;

use App\Modules\Catalog\Application\UseCases\ListLanguages\ListLanguagesCommand;
use App\Modules\Catalog\Application\UseCases\ListLanguages\ListLanguagesUseCase;
use App\Modules\Catalog\Infrastructure\Http\Resources\CatalogResponseResource;
use App\Modules\Catalog\Infrastructure\Http\Resources\LanguageResource;
use Illuminate\Http\JsonResponse;

final readonly class ListLanguagesTake
{
    public function __construct(
        private ListLanguagesUseCase $useCase,
    ) {}

    public function handle(): JsonResponse
    {
        $result = $this->useCase->handle(new ListLanguagesCommand);

        $data = array_map(fn ($l) => LanguageResource::make($l)->resolve(request()), $result);

        return CatalogResponseResource::make($data)->response();
    }
}
