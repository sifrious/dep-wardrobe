<?php

declare(strict_types=1);

namespace Sifrious\Wardrobe\Tests;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Sifrious\Wardrobe\LocalModel\AdmissionPolicy;
use Sifrious\Wardrobe\LocalModel\TrustedModelCatalogue;

final class TrustedModelCatalogueTest extends TestCase
{
    private const MODEL_ID = 'openai/gpt-oss-20b';
    private const SOURCE = 'https://huggingface.co/openai/gpt-oss-20b/resolve/f81fef1ddd90d214968e951a76834f1ded130a18/original/model.safetensors';
    private const DIGEST = '3340a61d1a0391e8c5b5d3463d18d4c48129a84bbc04a554c762c99020aa06ed';

    public function testBundledCatalogueHasExactlyOneApprovedModel(): void
    {
        $catalogue = TrustedModelCatalogue::bundled();

        self::assertSame('1.0.0', $catalogue->catalogueVersion);
        self::assertSame(AdmissionPolicy::VERSION, $catalogue->policyVersion);
        self::assertCount(1, $catalogue->approvedEntries());
        self::assertTrue($catalogue->decisionFor(self::MODEL_ID)->approved);
        self::assertSame(['model_not_catalogued'], $catalogue->decisionFor('unknown/model')->rejectionReasons);
    }

    public function testInstallerBoundaryRequiresExactApprovedSourceAndDigest(): void
    {
        $catalogue = TrustedModelCatalogue::bundled();

        self::assertTrue($catalogue->admitArtifact(self::SOURCE, self::DIGEST)->approved);
        self::assertSame(
            ['artifact_source_or_digest_not_approved'],
            $catalogue->admitArtifact(self::SOURCE, str_repeat('0', 64))->rejectionReasons,
        );
        self::assertSame(
            ['artifact_source_or_digest_not_approved'],
            $catalogue->admitArtifact('https://example.com/model.safetensors', self::DIGEST)->rejectionReasons,
        );
    }

    public function testProvenancePinsCataloguePolicyModelAndArtifact(): void
    {
        $provenance = TrustedModelCatalogue::bundled()->provenanceFor(self::MODEL_ID);

        self::assertSame('1.0.0', $provenance['catalogue_version']);
        self::assertSame('1.0.0', $provenance['policy_version']);
        self::assertSame('2025-08-05', $provenance['model_version']);
        self::assertSame('f81fef1ddd90d214968e951a76834f1ded130a18', $provenance['upstream_revision']);
        self::assertSame("sha256:" . self::DIGEST, $provenance['artifact_digest']);
        self::assertStringStartsWith('sha256:', $provenance['catalogue_digest']);
    }

    public function testCatalogueTamperingFailsIntegrityVerification(): void
    {
        $directory = sys_get_temp_dir() . '/wardrobe-' . bin2hex(random_bytes(8));
        mkdir($directory);
        file_put_contents("$directory/catalogue.json", '{"catalogue_version":"tampered"}');
        file_put_contents("$directory/catalogue.sha256", str_repeat('0', 64));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('failed integrity verification');

        try {
            TrustedModelCatalogue::fromVerifiedFiles(
                "$directory/catalogue.json",
                "$directory/catalogue.sha256",
                new AdmissionPolicy(),
            );
        } finally {
            unlink("$directory/catalogue.json");
            unlink("$directory/catalogue.sha256");
            rmdir($directory);
        }
    }
}
