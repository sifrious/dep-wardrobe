<?php

declare(strict_types=1);

namespace Sifrious\Wardrobe;

use InvalidArgumentException;

final class UsageReconciler
{
    public static function classify(AiUsage $existing, AiUsage $incoming): UsageRecordDisposition
    {
        if ($existing->reconciliationKey() !== $incoming->reconciliationKey()) {
            throw new InvalidArgumentException('Only observations with the same reconciliation key can be compared.');
        }

        if ($existing->contentFingerprint() === $incoming->contentFingerprint()) {
            return UsageRecordDisposition::Duplicate;
        }

        if ($incoming->replayedFromUsageId !== $existing->id) {
            throw new InvalidArgumentException('Changed usage requires explicit reconciliation lineage.');
        }

        return UsageRecordDisposition::Reconciled;
    }
}
