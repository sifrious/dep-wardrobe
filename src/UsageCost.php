<?php

declare(strict_types=1);

namespace Sifrious\Wardrobe;

use InvalidArgumentException;

final readonly class UsageCost
{
    public function __construct(
        public string $amount,
        public string $currency,
        public UsageValueSource $source,
    ) {
        if (preg_match('/^(?:0|[1-9]\d*)(?:\.\d+)?$/', $amount) !== 1) {
            throw new InvalidArgumentException('Usage cost must be a non-negative decimal string.');
        }

        if (preg_match('/^[A-Z]{3}$/', $currency) !== 1) {
            throw new InvalidArgumentException('Usage cost currency must be an ISO 4217 code.');
        }
    }

    /** @return array{amount:string,currency:string,source:string} */
    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'currency' => $this->currency,
            'source' => $this->source->value,
        ];
    }
}
