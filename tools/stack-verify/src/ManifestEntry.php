<?php

declare(strict_types=1);

namespace LockedStack\Verify;

final readonly class ManifestEntry
{
    public function __construct(
        public string $package,
        public string $lockedVersion,
        public Component $component,
    ) {
    }
}
