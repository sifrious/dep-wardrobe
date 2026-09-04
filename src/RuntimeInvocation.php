<?php

declare(strict_types=1);

namespace Sifrious\Wardrobe;

use InvalidArgumentException;

final readonly class RuntimeInvocation
{
    public function __construct(
        public string $runId,
        public string $runtime,
        public string $workspacePath,
        public string $prompt,
        public int $timeoutSeconds,
        public array $permissions = [],
        public ?string $model = null,
        public ?ProviderAccountReference $providerAccount = null,
    ) {
        if (trim($runId) === '' || trim($runtime) === '' || trim($workspacePath) === '' || trim($prompt) === '' || $timeoutSeconds < 1) {
            throw new InvalidArgumentException('A runtime invocation requires fixed run, runtime, workspace, prompt, and timeout values.');
        }

        if ($providerAccount !== null && !$providerAccount->supports($runtime, $model)) {
            throw new InvalidArgumentException('The provider account is not available for the selected runtime.');
        }
    }
}
