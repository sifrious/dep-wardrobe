<?php

declare(strict_types=1);

namespace Sifrious\Wardrobe;

enum ProviderAccountState: string
{
    case Available = 'available';
    case Unavailable = 'unavailable';
    case Disabled = 'disabled';
    case Revoked = 'revoked';
}
