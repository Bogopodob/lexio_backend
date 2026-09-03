<?php

namespace App\Modules\Catalog\Domain\Entities;

final readonly class WordForm
{
    public function __construct(
        public string $id,
        public string $translationId,
        public string $form,
        public string $formType,
    ) {}
}
