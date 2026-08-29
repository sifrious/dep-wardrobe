<?php

declare(strict_types=1);

namespace Sifrious\Wardrobe;

interface ProviderRuntimeObserver extends RuntimeObserver
{
    public function providerExecutionAcknowledged(string $providerExecutionId): void;
}
