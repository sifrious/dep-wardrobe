<?php

declare(strict_types=1);

namespace Sifrious\Wardrobe;

interface RuntimeAdapter
{
    public function supports(string $runtime): bool;
    public function invoke(RuntimeInvocation $invocation, RuntimeObserver $observer): RuntimeOutcome;
}
