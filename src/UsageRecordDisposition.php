<?php

declare(strict_types=1);

namespace Sifrious\Wardrobe;

enum UsageRecordDisposition: string
{
    case Recorded = 'recorded';
    case Duplicate = 'duplicate';
    case Reconciled = 'reconciled';
}
