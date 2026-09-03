<?php

declare(strict_types=1);

namespace Sifrious\Wardrobe;

interface RuntimeObserver
{
    public function event(string $type, array $payload = []): void;
    public function stdout(string $chunk): void;
    public function stderr(string $chunk): void;

    /** @param array{id:string,kind:string,path:string,media_type:string,size:int,hash:string,created_at:string} $artifact */
    public function artifact(array $artifact): void;

    /** @param list<string> $allowedResponses */
    public function needsInput(string $prompt, array $allowedResponses, string $resumeToken): void;

    /** @return array{resume_token:string,response:string}|null */
    public function continuation(): ?array;

    public function cancellationRequested(): bool;
}
