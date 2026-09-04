<?php

declare(strict_types=1);

namespace Sifrious\Wardrobe;

use InvalidArgumentException;

final readonly class UsageQuantity
{
    public function __construct(
        public string $metric,
        public string $unit,
        public string $value,
        public UsageValueSource $source,
    ) {
        if (trim($metric) === '' || trim($unit) === '') {
            throw new InvalidArgumentException('A usage quantity requires a metric and unit.');
        }

        if (!self::isNonNegativeDecimal($value)) {
            throw new InvalidArgumentException('Usage quantity values must be non-negative decimal strings.');
        }
    }

    /** @return array{metric:string,unit:string,value:string,source:string} */
    public function toArray(): array
    {
        return [
            'metric' => $this->metric,
            'unit' => $this->unit,
            'value' => $this->value,
            'source' => $this->source->value,
        ];
    }

    private static function isNonNegativeDecimal(string $value): bool
    {
        return preg_match('/^(?:0|[1-9]\d*)(?:\.\d+)?$/', $value) === 1;
    }
}
