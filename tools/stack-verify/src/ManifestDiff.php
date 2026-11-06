<?php

declare(strict_types=1);

namespace LockedStack\Verify;

final class ManifestDiff
{
    /**
     * @return list<string>
     */
    public static function versionChanges(Manifest $previous, Manifest $next): array
    {
        $changes = [];
        if ($previous->coreVersion !== $next->coreVersion) {
            $changes[] = "wordpress {$previous->coreVersion} -> {$next->coreVersion}";
        }

        foreach ($next->entries as $key => $entry) {
            $before = $previous->entries[$key] ?? null;
            if ($before === null) {
                $changes[] = "{$key} added at {$entry->lockedVersion}";
            } elseif ($before->lockedVersion !== $entry->lockedVersion) {
                $changes[] = "{$key} {$before->lockedVersion} -> {$entry->lockedVersion}";
            }
        }

        foreach (array_keys(array_diff_key($previous->entries, $next->entries)) as $key) {
            $changes[] = "{$key} removed";
        }

        return $changes;
    }
}
