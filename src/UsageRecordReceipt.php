<?php

declare(strict_types=1);

namespace Sifrious\Wardrobe;

use InvalidArgumentException;

final readonly class UsageRecordReceipt
{
    public function __construct(
        public string $usageId,
        public UsageRecordDisposition $disposition,
        public ?string $previousUsageId = null,
    ) {
        if (trim($usageId) === '') {
            throw new InvalidArgumentException('A usage receipt requires a usage ID.');
        }

        if ($disposition === UsageRecordDisposition::Recorded && $previousUsageId !== null) {
            throw new InvalidArgumentException('A newly recorded usage event cannot replace a previous event.');
        }

        if ($disposition !== UsageRecordDisposition::Recorded && ($previousUsageId === null || trim($previousUsageId) === '')) {
            throw new InvalidArgumentException('Duplicate and reconciled usage receipts require the previous usage ID.');
        }
    }
}
