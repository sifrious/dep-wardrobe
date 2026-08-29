<?php

declare(strict_types=1);

namespace Sifrious\Wardrobe;

interface RuntimeObserver
{
    public function event(string $type, array $payload = []): void;
    public function stdout(string $chunk): void;
    public function stderr(string $chunk): void;
    public function cancellationRequested(): bool;
}
