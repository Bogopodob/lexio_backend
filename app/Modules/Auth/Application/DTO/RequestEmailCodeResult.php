<?php

namespace App\Modules\Auth\Application\DTO;

final readonly class RequestEmailCodeResult
{
    public function __construct(
        public int $ttl,
        public ?string $debugCode,
        public string $deliveryChannel,
        public string $deliveryProvider,
    ) {}
}
