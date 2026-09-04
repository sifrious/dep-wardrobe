<?php

declare(strict_types=1);

namespace Sifrious\Wardrobe\LocalModel;

final readonly class AdmissionDecision
{
    /** @param list<string> $rejectionReasons */
    public function __construct(
        public bool $approved,
        public array $rejectionReasons,
    ) {
    }
}
