<?php

declare(strict_types=1);

namespace Sifrious\Wardrobe;

interface RuntimeAdapterContract
{
    public function supports(string $runtime): bool;
}
