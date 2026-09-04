<?php

declare(strict_types=1);

namespace Sifrious\Wardrobe;

use InvalidArgumentException;

/**
 * An external-provider invocation that cannot exist without an account-scoped
 * connection.
 */
final readonly class ProviderRuntimeInvocation
{
    public ProviderAccountReference $providerAccount;

    public function __construct(public RuntimeInvocation $invocation)
    {
        if ($invocation->providerAccount === null) {
            throw new InvalidArgumentException('An external provider invocation requires an account-scoped provider connection.');
        }

        $this->providerAccount = $invocation->providerAccount;
    }
}
