<?php

declare(strict_types=1);

namespace Sifrious\Wardrobe\LocalModel;

use JsonException;
use RuntimeException;

final readonly class TrustedModelCatalogue
{
    /**
     * @param list<array<string, mixed>> $entries
     * @param array<string, AdmissionDecision> $decisions
     */
    private function __construct(
        public string $catalogueVersion,
        public string $policyVersion,
        public string $catalogueDigest,
        private array $entries,
        private array $decisions,
    ) {
    }

    public static function bundled(): self
    {
        $root = dirname(__DIR__, 2);

        return self::fromVerifiedFiles(
            "$root/resources/local-model-catalogue.v1.json",
            "$root/resources/local-model-catalogue.v1.sha256",
            new AdmissionPolicy(),
        );
    }

    public static function fromVerifiedFiles(
        string $cataloguePath,
        string $checksumPath,
        AdmissionPolicy $policy,
    ): self {
        $json = file_get_contents($cataloguePath);
        $checksum = trim((string) file_get_contents($checksumPath));
        if ($json === false || preg_match('/^[a-f0-9]{64}$/D', $checksum) !== 1) {
            throw new RuntimeException('The model catalogue or its SHA-256 checksum is unreadable.');
        }

        $actualChecksum = hash('sha256', $json);
        if (!hash_equals($checksum, $actualChecksum)) {
            throw new RuntimeException('The model catalogue failed integrity verification.');
        }

        try {
            $document = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('The model catalogue is not valid JSON.', previous: $exception);
        }

        if (!is_array($document)
            || !is_string($document['catalogue_version'] ?? null)
            || ($document['policy_version'] ?? null) !== AdmissionPolicy::VERSION
            || !is_array($document['entries'] ?? null)
        ) {
            throw new RuntimeException('The model catalogue contract is invalid or uses an unsupported policy.');
        }

        $entries = [];
        $decisions = [];
        foreach ($document['entries'] as $entry) {
            if (!is_array($entry) || !is_string($entry['identity']['model_id'] ?? null)) {
                throw new RuntimeException('A model catalogue entry has no stable identity.');
            }

            $decision = $policy->evaluate($entry);
            $declaredDecision = $entry['admission']['decision'] ?? null;
            $declaredReasons = $entry['admission']['rejection_reasons'] ?? null;
            if ($declaredDecision !== ($decision->approved ? 'approved' : 'rejected')
                || $declaredReasons !== $decision->rejectionReasons
            ) {
                throw new RuntimeException("The declared admission decision for {$entry['identity']['model_id']} does not match policy.");
            }

            $modelId = $entry['identity']['model_id'];
            if (array_key_exists($modelId, $decisions)) {
                throw new RuntimeException("Duplicate model identity in catalogue: $modelId.");
            }
            $entries[] = $entry;
            $decisions[$modelId] = $decision;
        }

        return new self(
            $document['catalogue_version'],
            $document['policy_version'],
            "sha256:$actualChecksum",
            $entries,
            $decisions,
        );
    }

    /** @return list<array<string, mixed>> */
    public function entries(): array
    {
        return $this->entries;
    }

    /** @return list<array<string, mixed>> */
    public function approvedEntries(): array
    {
        return array_values(array_filter(
            $this->entries,
            fn (array $entry): bool => $this->decisions[$entry['identity']['model_id']]->approved,
        ));
    }

    public function decisionFor(string $modelId): AdmissionDecision
    {
        return $this->decisions[$modelId] ?? new AdmissionDecision(false, ['model_not_catalogued']);
    }

    public function admitArtifact(string $source, string $sha256Digest): AdmissionDecision
    {
        foreach ($this->approvedEntries() as $entry) {
            if (hash_equals($entry['artifact']['source'], $source)
                && hash_equals($entry['artifact']['digest'], strtolower($sha256Digest))
            ) {
                return new AdmissionDecision(true, []);
            }
        }

        return new AdmissionDecision(false, ['artifact_source_or_digest_not_approved']);
    }

    /** @return array<string, string>|null */
    public function provenanceFor(string $modelId): ?array
    {
        foreach ($this->approvedEntries() as $entry) {
            if ($entry['identity']['model_id'] === $modelId) {
                return [
                    'catalogue_version' => $this->catalogueVersion,
                    'catalogue_digest' => $this->catalogueDigest,
                    'policy_version' => $this->policyVersion,
                    'model_id' => $entry['identity']['model_id'],
                    'model_version' => $entry['identity']['model_version'],
                    'upstream_revision' => $entry['identity']['upstream_revision'],
                    'artifact_source' => $entry['artifact']['source'],
                    'artifact_digest' => "sha256:{$entry['artifact']['digest']}",
                ];
            }
        }

        return null;
    }
}
