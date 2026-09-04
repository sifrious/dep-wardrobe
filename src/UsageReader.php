<?php

declare(strict_types=1);

namespace Sifrious\Wardrobe;

interface UsageReader
{
    /** @return iterable<AiUsage> */
    public function forRun(string $runId): iterable;
}
