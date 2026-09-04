<?php

declare(strict_types=1);

namespace Sifrious\Wardrobe;

enum UsageAccountScope: string
{
    case ProviderAccount = 'provider_account';
    case LocalRuntime = 'local_runtime';
}
