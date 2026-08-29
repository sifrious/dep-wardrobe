<?php

declare(strict_types=1);

namespace Sifrious\Wardrobe;

final readonly class RuntimeOutcome
{
    public function __construct(
        public string $status,
        public ?int $exitCode = null,
        public ?string $reason = null,
    ) {}
}
