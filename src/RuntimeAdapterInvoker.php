<?php

declare(strict_types=1);

namespace Sifrious\Wardrobe;

use InvalidArgumentException;

/**
 * Enforced invocation seam for local and external-provider adapters.
 */
final class RuntimeAdapterInvoker
{
    public function invoke(
        RuntimeAdapterContract $adapter,
        RuntimeInvocation $invocation,
        RuntimeObserver $observer,
    ): RuntimeOutcome {
        if (!$adapter->supports($invocation->runtime)) {
            throw new InvalidArgumentException('The adapter does not support the selected runtime.');
        }

        if ($adapter instanceof ProviderRuntimeAdapter) {
            if (!$observer instanceof ProviderRuntimeObserver) {
                throw new InvalidArgumentException('An external provider adapter requires provider acknowledgement observation.');
            }

            return $adapter->invoke(new ProviderRuntimeInvocation($invocation), $observer);
        }

        if (!$adapter instanceof RuntimeAdapter) {
            throw new InvalidArgumentException('Unsupported runtime adapter contract.');
        }

        if ($invocation->providerAccount !== null) {
            throw new InvalidArgumentException('A local adapter cannot receive an external provider account.');
        }

        return $adapter->invoke($invocation, $observer);
    }
}
