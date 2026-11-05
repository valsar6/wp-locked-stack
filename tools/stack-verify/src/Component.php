<?php

declare(strict_types=1);

namespace LockedStack\Verify;

final readonly class Component
{
    public function __construct(
        public string $key,
        public ?string $declaredVersion,
        public string $treeHash,
    ) {
    }
}
