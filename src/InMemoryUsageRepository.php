<?php

declare(strict_types=1);

namespace Sifrious\Wardrobe;

/**
 * Reference implementation for fixtures and recorder conformance tests.
 *
 * Production implementations require a durable unique constraint on the same
 * reconciliation key and must preserve superseded records for audit.
 */
final class InMemoryUsageRepository implements UsageReader, UsageRecorder
{
    /** @var array<string, AiUsage> */
    private array $currentByReconciliationKey = [];

    public function record(AiUsage $usage): UsageRecordReceipt
    {
        $key = $usage->reconciliationKey();
        $existing = $this->currentByReconciliationKey[$key] ?? null;

        if ($existing === null) {
            $this->currentByReconciliationKey[$key] = $usage;

            return new UsageRecordReceipt($usage->id, UsageRecordDisposition::Recorded);
        }

        $disposition = UsageReconciler::classify($existing, $usage);

        if ($disposition === UsageRecordDisposition::Reconciled) {
            $this->currentByReconciliationKey[$key] = $usage;
        }

        return new UsageRecordReceipt($usage->id, $disposition, $existing->id);
    }

    public function forRun(string $runId): iterable
    {
        foreach ($this->currentByReconciliationKey as $usage) {
            if ($usage->runId === $runId) {
                yield $usage;
            }
        }
    }
}
