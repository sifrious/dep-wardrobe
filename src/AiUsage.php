<?php

declare(strict_types=1);

namespace Sifrious\Wardrobe;

use DateTimeImmutable;
use InvalidArgumentException;

final readonly class AiUsage
{
    public const CONTRACT_VERSION = '1';

    public ?string $providerAccountId;

    private const RECONCILIATION_METADATA_KEYS = [
        'billing_tier',
        'invoice_line_id',
        'project_id',
        'region',
        'usage_scope',
        'workspace_id',
    ];

    /**
     * @param list<UsageQuantity> $quantities
     * @param array<string, bool|int|float|string|null> $reconciliationMetadata
     */
    public function __construct(
        public string $id,
        public string $runId,
        public string $logicalRequestId,
        public string $attemptId,
        public int $attemptNumber,
        public string $provider,
        public UsageAccountScope $accountScope,
        public string $accountScopeId,
        public string $runtime,
        public string $operation,
        public string $reconciliationId,
        public string $observedAt,
        public array $quantities,
        public ?string $model = null,
        public ?string $providerExecutionId = null,
        public ?string $providerRequestId = null,
        public ?string $providerUsageId = null,
        public ?string $retryOfAttemptId = null,
        public ?string $replayedFromUsageId = null,
        public ?string $providerRecordedAt = null,
        public ?UsageCost $cost = null,
        public array $reconciliationMetadata = [],
    ) {
        foreach ([
            $id,
            $runId,
            $logicalRequestId,
            $attemptId,
            $provider,
            $accountScopeId,
            $runtime,
            $operation,
            $reconciliationId,
        ] as $value) {
            if (trim($value) === '') {
                throw new InvalidArgumentException('Usage identity fields must not be empty.');
            }
        }

        $this->providerAccountId = $accountScope === UsageAccountScope::ProviderAccount
            ? $accountScopeId
            : null;

        if ($attemptNumber < 1) {
            throw new InvalidArgumentException('Usage attempt number must be positive.');
        }

        if ($attemptNumber === 1 && $retryOfAttemptId !== null) {
            throw new InvalidArgumentException('A first attempt cannot identify a retry source.');
        }

        if ($attemptNumber > 1 && ($retryOfAttemptId === null || trim($retryOfAttemptId) === '')) {
            throw new InvalidArgumentException('A retry must identify the preceding attempt.');
        }

        self::assertTimestamp($observedAt, 'Observed timestamp');
        if ($providerRecordedAt !== null) {
            self::assertTimestamp($providerRecordedAt, 'Provider timestamp');
        }

        if (!array_is_list($quantities) || $quantities === []) {
            throw new InvalidArgumentException('Usage must include at least one normalized quantity.');
        }

        foreach ($quantities as $quantity) {
            if (!$quantity instanceof UsageQuantity) {
                throw new InvalidArgumentException('Usage quantities must be UsageQuantity values.');
            }
        }

        foreach ($reconciliationMetadata as $key => $value) {
            if (!is_string($key) || !in_array($key, self::RECONCILIATION_METADATA_KEYS, true)) {
                throw new InvalidArgumentException('Reconciliation metadata contains an unsupported field.');
            }

            if (!is_scalar($value) && $value !== null) {
                throw new InvalidArgumentException('Reconciliation metadata must contain named scalar values.');
            }
        }
    }

    public function reconciliationKey(): string
    {
        $identity = json_encode([
            'provider' => $this->provider,
            'account_scope' => $this->accountScope->value,
            'account_scope_id' => $this->accountScopeId,
            'reconciliation_id' => $this->reconciliationId,
        ], JSON_THROW_ON_ERROR);

        return 'usage:v1:'.hash('sha256', $identity);
    }

    /**
     * Stable fingerprint for deciding whether a redelivered reconciliation key
     * is an exact duplicate or an explicit corrected observation.
     */
    public function contentFingerprint(): string
    {
        $payload = $this->toArray();
        unset($payload['id'], $payload['observed_at'], $payload['replayed_from_usage_id']);

        return hash('sha256', (string) json_encode($payload, JSON_THROW_ON_ERROR));
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'contract_version' => self::CONTRACT_VERSION,
            'id' => $this->id,
            'run_id' => $this->runId,
            'logical_request_id' => $this->logicalRequestId,
            'attempt_id' => $this->attemptId,
            'attempt_number' => $this->attemptNumber,
            'retry_of_attempt_id' => $this->retryOfAttemptId,
            'replayed_from_usage_id' => $this->replayedFromUsageId,
            'provider' => $this->provider,
            'account_scope' => $this->accountScope->value,
            'account_scope_id' => $this->accountScopeId,
            'runtime' => $this->runtime,
            'model' => $this->model,
            'operation' => $this->operation,
            'provider_account_id' => $this->providerAccountId,
            'provider_execution_id' => $this->providerExecutionId,
            'provider_request_id' => $this->providerRequestId,
            'provider_usage_id' => $this->providerUsageId,
            'reconciliation_id' => $this->reconciliationId,
            'quantities' => array_map(
                static fn (UsageQuantity $quantity): array => $quantity->toArray(),
                $this->quantities,
            ),
            'cost' => $this->cost?->toArray(),
            'observed_at' => $this->observedAt,
            'provider_recorded_at' => $this->providerRecordedAt,
            'reconciliation_metadata' => $this->reconciliationMetadata,
        ];
    }

    private static function assertTimestamp(string $value, string $name): void
    {
        $parsed = DateTimeImmutable::createFromFormat(DateTimeImmutable::ATOM, $value);

        if ($parsed === false || $parsed->format(DateTimeImmutable::ATOM) !== $value) {
            throw new InvalidArgumentException($name.' must be an ISO 8601 timestamp with an offset.');
        }
    }
}
