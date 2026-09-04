<?php

declare(strict_types=1);

namespace Sifrious\Wardrobe\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sifrious\Wardrobe\LocalModel\AdmissionPolicy;

final class AdmissionPolicyTest extends TestCase
{
    /** @return iterable<string, array{array<string, mixed>, bool, list<string>}> */
    public static function admissionCases(): iterable
    {
        $root = dirname(__DIR__);
        $catalogue = json_decode(
            (string) file_get_contents("$root/resources/local-model-catalogue.v1.json"),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        $cases = json_decode(
            (string) file_get_contents(__DIR__ . '/fixtures/admission-cases.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        foreach ($cases as $case) {
            $entry = $catalogue['entries'][0];
            foreach ($case['changes'] as $path => $value) {
                self::setPath($entry, $path, $value);
            }

            yield $case['name'] => [$entry, $case['approved'], $case['rejection_reasons']];
        }
    }

    /**
     * @param array<string, mixed> $entry
     * @param list<string> $rejectionReasons
     */
    #[DataProvider('admissionCases')]
    public function testAdmissionPolicy(
        array $entry,
        bool $approved,
        array $rejectionReasons,
    ): void {
        $decision = (new AdmissionPolicy())->evaluate($entry);

        self::assertSame($approved, $decision->approved);
        foreach ($rejectionReasons as $reason) {
            self::assertContains($reason, $decision->rejectionReasons);
        }
        if ($approved) {
            self::assertSame([], $decision->rejectionReasons);
        }
    }

    /** @param array<string, mixed> $entry */
    private static function setPath(array &$entry, string $path, mixed $value): void
    {
        $target = &$entry;
        foreach (explode('.', $path) as $segment) {
            $target = &$target[$segment];
        }
        $target = $value;
    }
}
