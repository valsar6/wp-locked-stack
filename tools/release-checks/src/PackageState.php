<?php

declare(strict_types=1);

namespace LockedStack\ReleaseChecks;

final readonly class PackageState
{
    public function __construct(
        public string $name,
        public string $declaredVersion,
        public ?string $headerVersion,
        public ?string $lockedVersion,
        public ?string $latestTagVersion,
        public bool $changedSinceTag,
    ) {
    }

    /**
     * @return list<string>
     */
    public function problems(): array
    {
        $problems = [];
        if ($this->headerVersion !== $this->declaredVersion) {
            $problems[] = "{$this->name}: header says " . ($this->headerVersion ?? 'nothing') . ", composer.json says {$this->declaredVersion}";
        }
        if ($this->lockedVersion !== $this->declaredVersion) {
            $problems[] = "{$this->name}: composer.lock says " . ($this->lockedVersion ?? 'nothing') . ", composer.json says {$this->declaredVersion}";
        }
        if ($this->latestTagVersion === null) {
            return $problems;
        }

        if (version_compare($this->declaredVersion, $this->latestTagVersion, '<')) {
            $problems[] = "{$this->name}: {$this->declaredVersion} is older than released {$this->latestTagVersion}";
        } elseif ($this->declaredVersion === $this->latestTagVersion && $this->changedSinceTag) {
            $problems[] = "{$this->name}: code changed since release {$this->latestTagVersion}, bump the version";
        }

        return $problems;
    }
}
