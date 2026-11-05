<?php

declare(strict_types=1);

namespace LockedStack\Verify;

use RuntimeException;

final readonly class Manifest
{
    /**
     * @param array<string, ManifestEntry> $entries
     */
    public function __construct(
        public string $coreVersion,
        public array $entries,
    ) {
    }

    public static function fromFile(string $path): self
    {
        $json = @file_get_contents($path);
        if ($json === false) {
            throw new RuntimeException("Cannot read manifest: {$path}");
        }

        $data = json_decode($json, true, 16, JSON_THROW_ON_ERROR);
        if (!is_array($data) || !is_string($data['core'] ?? null) || !is_array($data['components'] ?? null)) {
            throw new RuntimeException("Malformed manifest: {$path}");
        }

        $entries = [];
        foreach ($data['components'] as $key => $row) {
            if (
                !is_string($key)
                || !is_array($row)
                || !is_string($row['package'] ?? null)
                || !is_string($row['version'] ?? null)
                || !is_string($row['treeHash'] ?? null)
            ) {
                throw new RuntimeException("Malformed manifest entry in {$path}");
            }
            $declared = $row['declaredVersion'] ?? null;
            $entries[$key] = new ManifestEntry(
                $row['package'],
                $row['version'],
                new Component($key, is_string($declared) ? $declared : null, $row['treeHash']),
            );
        }

        return new self($data['core'], $entries);
    }

    public function toJson(): string
    {
        $components = [];
        foreach ($this->entries as $key => $entry) {
            $components[$key] = [
                'package' => $entry->package,
                'version' => $entry->lockedVersion,
                'declaredVersion' => $entry->component->declaredVersion,
                'treeHash' => $entry->component->treeHash,
            ];
        }
        ksort($components);

        return json_encode(
            ['core' => $this->coreVersion, 'components' => $components],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        ) . "\n";
    }
}
