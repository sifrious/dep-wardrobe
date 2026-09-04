<?php

declare(strict_types=1);

namespace Sifrious\Wardrobe;

use InvalidArgumentException;

/**
 * Portable, non-secret identity for an account-scoped runtime connection.
 *
 * The owning host resolves connectionId inside its credential adapter. Wardrobe
 * consumers must never attach credentials, authorization headers, or provider
 * SDK objects to this value.
 */
final readonly class ProviderAccountReference
{
    public const CONTRACT_VERSION = '1';

    /**
     * @param list<string> $allowedRuntimes
     * @param list<string> $allowedModels
     */
    public function __construct(
        public string $id,
        public string $ownerAccountId,
        public string $connectionId,
        public string $provider,
        public string $providerAccountId,
        public ProviderAccountState $state,
        public ?string $displayName = null,
        public array $allowedRuntimes = [],
        public array $allowedModels = [],
    ) {
        foreach ([$id, $ownerAccountId, $connectionId, $provider, $providerAccountId] as $value) {
            if (trim($value) === '') {
                throw new InvalidArgumentException('Provider account identity fields must not be empty.');
            }
        }

        self::assertStringList($allowedRuntimes, 'Allowed runtimes');
        self::assertStringList($allowedModels, 'Allowed models');
    }

    public function supports(string $runtime, ?string $model = null): bool
    {
        if ($this->state !== ProviderAccountState::Available) {
            return false;
        }

        if ($this->allowedRuntimes !== [] && !in_array($runtime, $this->allowedRuntimes, true)) {
            return false;
        }

        return $model === null
            || $this->allowedModels === []
            || in_array($model, $this->allowedModels, true);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'contract_version' => self::CONTRACT_VERSION,
            'id' => $this->id,
            'owner_account_id' => $this->ownerAccountId,
            'connection_id' => $this->connectionId,
            'provider' => $this->provider,
            'provider_account_id' => $this->providerAccountId,
            'state' => $this->state->value,
            'display_name' => $this->displayName,
            'allowed_runtimes' => $this->allowedRuntimes,
            'allowed_models' => $this->allowedModels,
        ];
    }

    /** @param list<string> $values */
    private static function assertStringList(array $values, string $name): void
    {
        if (array_is_list($values) === false) {
            throw new InvalidArgumentException($name.' must be a list.');
        }

        foreach ($values as $value) {
            if (!is_string($value) || trim($value) === '') {
                throw new InvalidArgumentException($name.' must contain non-empty strings.');
            }
        }

        if (count($values) !== count(array_unique($values))) {
            throw new InvalidArgumentException($name.' must not contain duplicates.');
        }
    }
}
