<?php

declare(strict_types=1);

namespace Sifrious\Wardrobe;

/**
 * A runtime adapter that creates or resumes an execution owned by an external
 * provider. The adapter must acknowledge that provider identity through a
 * ProviderRuntimeObserver before emitting output or returning an outcome.
 *
 * ProviderRuntimeInvocation makes account-less direct calls unrepresentable.
 */
interface ProviderRuntimeAdapter extends RuntimeAdapterContract
{
    public function invoke(
        ProviderRuntimeInvocation $invocation,
        ProviderRuntimeObserver $observer,
    ): RuntimeOutcome;
}
