<?php

declare(strict_types=1);

namespace Sifrious\Wardrobe;

enum UsageValueSource: string
{
    case ProviderReported = 'provider_reported';
    case Measured = 'measured';
    case Estimated = 'estimated';
    case Derived = 'derived';
}
