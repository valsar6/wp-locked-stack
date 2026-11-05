<?php

declare(strict_types=1);

namespace LockedStack\Verify;

final readonly class Drift
{
    public function __construct(
        public DriftKind $kind,
        public string $subject,
        public ?string $expected = null,
        public ?string $actual = null,
    ) {
    }

    public function describe(): string
    {
        $line = "{$this->kind->value}: {$this->subject}";
        if ($this->expected !== null || $this->actual !== null) {
            $line .= sprintf(' (expected %s, found %s)', $this->expected ?? 'nothing', $this->actual ?? 'nothing');
        }

        return $line;
    }
}
