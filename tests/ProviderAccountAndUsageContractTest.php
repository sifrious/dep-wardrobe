<?php

declare(strict_types=1);

namespace Sifrious\Wardrobe\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Sifrious\Wardrobe\AiUsage;
use Sifrious\Wardrobe\ProviderAccountReference;
use Sifrious\Wardrobe\ProviderAccountState;
use Sifrious\Wardrobe\RuntimeInvocation;
use Sifrious\Wardrobe\UsageCost;
use Sifrious\Wardrobe\UsageQuantity;
use Sifrious\Wardrobe\UsageReconciler;
use Sifrious\Wardrobe\UsageRecordDisposition;
use Sifrious\Wardrobe\UsageValueSource;

final class ProviderAccountAndUsageContractTest extends TestCase
{
    public function test_account_reference_is_account_scoped_and_contains_no_auth_or_secret_material(): void
    {
        $account = self::ampAccount();
        $export = $account->toArray();

        self::assertSame('acc_zahir_123', $export['owner_account_id']);
        self::assertSame('conn_amp_456', $export['connection_id']);
        self::assertSame('amp-account-789', $export['provider_account_id']);
        self::assertSame(
            [],
            array_intersect(
                ['credential', 'secret', 'token', 'entitlement', 'execution_authorization'],
                array_keys($export),
            ),
        );
        self::assertTrue($account->supports('amp-orb', 'claude-sonnet'));
        self::assertFalse($account->supports('local-llama'));
    }

    public function test_local_runtime_does_not_require_an_external_provider_account(): void
    {
        $invocation = new RuntimeInvocation(
            runId: 'run-local-1',
            runtime: 'local-llama',
            workspacePath: '/workspace/repository',
            prompt: 'Summarize the repository.',
            timeoutSeconds: 60,
        );

        self::assertNull($invocation->providerAccount);
    }

    public function test_unavailable_or_incompatible_provider_account_cannot_be_selected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new RuntimeInvocation(
            runId: 'run-amp-1',
            runtime: 'local-llama',
            workspacePath: '/workspace/repository',
            prompt: 'Summarize the repository.',
            timeoutSeconds: 60,
            providerAccount: self::ampAccount(),
        );
    }

    public function test_two_providers_keep_distinct_units_and_value_sources(): void
    {
        $amp = self::usage(
            id: 'usage-amp-1',
            provider: 'amp',
            runtime: 'amp-orb',
            reconciliationId: 'amp-request-1',
            quantities: [
                new UsageQuantity('input', 'token', '1200', UsageValueSource::ProviderReported),
                new UsageQuantity('output', 'token', '350', UsageValueSource::ProviderReported),
            ],
            providerAccountId: 'provider-account-1',
            providerRequestId: 'amp-request-1',
            cost: new UsageCost('0.0175', 'USD', UsageValueSource::ProviderReported),
        );
        $local = self::usage(
            id: 'usage-local-1',
            provider: 'local',
            runtime: 'local-llama',
            reconciliationId: 'run-local-1:attempt-1:final',
            quantities: [
                new UsageQuantity('compute', 'second', '12.75', UsageValueSource::Measured),
                new UsageQuantity('energy', 'watt_hour', '0.42', UsageValueSource::Estimated),
            ],
        );

        self::assertSame('token', $amp->toArray()['quantities'][0]['unit']);
        self::assertSame('provider_reported', $amp->toArray()['cost']['source']);
        self::assertSame('second', $local->toArray()['quantities'][0]['unit']);
        self::assertSame('estimated', $local->toArray()['quantities'][1]['source']);
        self::assertNull($local->providerAccountId);
    }

    public function test_replay_is_deduplicated_and_changed_observation_requires_explicit_reconciliation(): void
    {
        $existing = self::usage(
            id: 'usage-1',
            provider: 'amp',
            runtime: 'amp-orb',
            reconciliationId: 'amp-request-1',
            quantities: [new UsageQuantity('output', 'token', '100', UsageValueSource::ProviderReported)],
            providerRequestId: 'amp-request-1',
        );
        $duplicate = self::usage(
            id: 'usage-duplicate',
            provider: 'amp',
            runtime: 'amp-orb',
            reconciliationId: 'amp-request-1',
            quantities: [new UsageQuantity('output', 'token', '100', UsageValueSource::ProviderReported)],
            providerRequestId: 'amp-request-1',
            replayedFromUsageId: 'usage-1',
        );
        $corrected = self::usage(
            id: 'usage-corrected',
            provider: 'amp',
            runtime: 'amp-orb',
            reconciliationId: 'amp-request-1',
            quantities: [new UsageQuantity('output', 'token', '110', UsageValueSource::ProviderReported)],
            providerRequestId: 'amp-request-1',
            replayedFromUsageId: 'usage-1',
        );

        self::assertSame(UsageRecordDisposition::Duplicate, UsageReconciler::classify($existing, $duplicate));
        self::assertSame(UsageRecordDisposition::Reconciled, UsageReconciler::classify($existing, $corrected));
    }

    public function test_retry_has_distinct_reconciliation_identity_and_explicit_attempt_lineage(): void
    {
        $first = self::usage(
            id: 'usage-attempt-1',
            provider: 'amp',
            runtime: 'amp-orb',
            reconciliationId: 'amp-request-1',
            quantities: [new UsageQuantity('request', 'count', '1', UsageValueSource::ProviderReported)],
            providerRequestId: 'amp-request-1',
        );
        $retry = self::usage(
            id: 'usage-attempt-2',
            provider: 'amp',
            runtime: 'amp-orb',
            reconciliationId: 'amp-request-2',
            quantities: [new UsageQuantity('request', 'count', '1', UsageValueSource::ProviderReported)],
            providerRequestId: 'amp-request-2',
            attemptId: 'attempt-2',
            attemptNumber: 2,
            retryOfAttemptId: 'attempt-1',
        );

        self::assertNotSame($first->reconciliationKey(), $retry->reconciliationKey());
        self::assertSame('attempt-1', $retry->retryOfAttemptId);
    }

    public function test_secret_bearing_reconciliation_metadata_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        self::usage(
            id: 'usage-secret',
            provider: 'amp',
            runtime: 'amp-orb',
            reconciliationId: 'amp-request-secret',
            quantities: [new UsageQuantity('request', 'count', '1', UsageValueSource::ProviderReported)],
            reconciliationMetadata: ['access_token' => 'not-a-real-token'],
        );
    }

    private static function ampAccount(): ProviderAccountReference
    {
        return new ProviderAccountReference(
            id: 'provider-account-1',
            ownerAccountId: 'acc_zahir_123',
            connectionId: 'conn_amp_456',
            provider: 'amp',
            providerAccountId: 'amp-account-789',
            state: ProviderAccountState::Available,
            displayName: 'Example Amp workspace',
            allowedRuntimes: ['amp-orb'],
            allowedModels: ['claude-sonnet'],
        );
    }

    /**
     * @param list<UsageQuantity> $quantities
     * @param array<string, bool|int|float|string|null> $reconciliationMetadata
     */
    private static function usage(
        string $id,
        string $provider,
        string $runtime,
        string $reconciliationId,
        array $quantities,
        ?string $providerAccountId = null,
        ?string $providerRequestId = null,
        ?UsageCost $cost = null,
        string $attemptId = 'attempt-1',
        int $attemptNumber = 1,
        ?string $retryOfAttemptId = null,
        ?string $replayedFromUsageId = null,
        array $reconciliationMetadata = [],
    ): AiUsage {
        return new AiUsage(
            id: $id,
            runId: 'run-1',
            logicalRequestId: 'logical-request-1',
            attemptId: $attemptId,
            attemptNumber: $attemptNumber,
            provider: $provider,
            runtime: $runtime,
            operation: 'agent_execution',
            reconciliationId: $reconciliationId,
            observedAt: '2026-09-04T13:00:00+00:00',
            quantities: $quantities,
            model: $provider === 'amp' ? 'claude-sonnet' : 'llama',
            providerAccountId: $providerAccountId,
            providerExecutionId: $provider === 'amp' ? 'orb-thread-1' : null,
            providerRequestId: $providerRequestId,
            retryOfAttemptId: $retryOfAttemptId,
            replayedFromUsageId: $replayedFromUsageId,
            cost: $cost,
            reconciliationMetadata: $reconciliationMetadata,
        );
    }
}
