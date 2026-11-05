<?php

declare(strict_types=1);

namespace LockedStack\Verify;

final class Verifier
{
    /**
     * @return list<Drift>
     */
    public static function verify(Manifest $manifest, string $root): array
    {
        $drifts = [];

        $core = CoreVersion::read($root);
        if ($core !== $manifest->coreVersion) {
            $drifts[] = new Drift(DriftKind::CoreVersion, 'wordpress', $manifest->coreVersion, $core);
        }

        $scanned = ComponentScanner::scan($root);
        foreach ($manifest->entries as $key => $entry) {
            $component = $scanned[$key] ?? null;
            if ($component === null) {
                $drifts[] = new Drift(DriftKind::Missing, $key);
                continue;
            }

            $expected = $entry->component;
            if ($component->declaredVersion !== $expected->declaredVersion) {
                $drifts[] = new Drift(
                    DriftKind::VersionMismatch,
                    $key,
                    $expected->declaredVersion,
                    $component->declaredVersion,
                );
            } elseif ($component->treeHash !== $expected->treeHash) {
                $drifts[] = new Drift(
                    DriftKind::ContentMismatch,
                    $key,
                    substr($expected->treeHash, 0, 12),
                    substr($component->treeHash, 0, 12),
                );
            }
        }

        foreach (array_keys(array_diff_key($scanned, $manifest->entries)) as $key) {
            $drifts[] = new Drift(DriftKind::Unexpected, $key);
        }

        return $drifts;
    }
}
