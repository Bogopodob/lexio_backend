<?php

namespace App\Modules\Library\Application\UseCases\UploadMedia;

final readonly class UploadMediaCommand
{
    public function __construct(
        public string $userId,
        public string $kind,
    ) {}
}
