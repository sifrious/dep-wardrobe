<?php

declare(strict_types=1);

namespace Sifrious\Wardrobe;

/**
 * Durable implementations must enforce atomic uniqueness on
 * AiUsage::reconciliationKey().
 *
 * A repeated key with the same content fingerprint is Duplicate. A changed
 * observation is accepted only when replayedFromUsageId names the current
 * event, and is Reconciled. Retries use a new reconciliation ID and explicit
 * retryOfAttemptId lineage.
 */
interface UsageRecorder
{
    public function record(AiUsage $usage): UsageRecordReceipt;
}
