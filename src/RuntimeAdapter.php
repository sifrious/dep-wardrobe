<?php

declare(strict_types=1);

namespace Sifrious\Wardrobe;

/**
 * Credential-free adapter for a local or host-owned runtime.
 */
interface RuntimeAdapter extends RuntimeAdapterContract
{
    public function invoke(RuntimeInvocation $invocation, RuntimeObserver $observer): RuntimeOutcome;
}
