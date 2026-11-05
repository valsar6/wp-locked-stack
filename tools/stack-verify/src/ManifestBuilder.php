<?php

declare(strict_types=1);

namespace LockedStack\Verify;

use RuntimeException;

final class ManifestBuilder
{
    private const array KINDS = [
        'wordpress-plugin' => 'plugin',
        'wordpress-theme' => 'theme',
        'wordpress-muplugin' => 'mu-plugin',
    ];

    public static function build(string $lockPath, string $root): Manifest
    {
        $core = CoreVersion::read($root);
        if ($core === null) {
            throw new RuntimeException("Cannot read the WordPress version under {$root}");
        }

        $scanned = ComponentScanner::scan($root);
        $entries = [];
        foreach (self::lockedPackages($lockPath) as $package) {
            $key = $package['key'];
            $component = $scanned[$key] ?? null;
            if ($component === null) {
                throw new RuntimeException("{$package['name']} is locked but {$key} is not in the tree");
            }
            if ($package['firstParty'] && $component->declaredVersion !== $package['version']) {
                throw new RuntimeException(sprintf(
                    '%s declares version %s but the lockfile says %s',
                    $key,
                    $component->declaredVersion ?? 'nothing',
                    $package['version'],
                ));
            }
            $entries[$key] = new ManifestEntry($package['name'], $package['version'], $component);
        }

        $unlisted = array_diff_key($scanned, $entries);
        if ($unlisted !== []) {
            throw new RuntimeException('Not in the lockfile: ' . implode(', ', array_keys($unlisted)));
        }

        return new Manifest($core, $entries);
    }

    /**
     * @return list<array{name: string, key: string, version: string, firstParty: bool}>
     */
    private static function lockedPackages(string $lockPath): array
    {
        $json = file_get_contents($lockPath);
        if ($json === false) {
            throw new RuntimeException("Cannot read lockfile: {$lockPath}");
        }
        $lock = json_decode($json, true, 64, JSON_THROW_ON_ERROR);
        if (!is_array($lock) || !is_array($lock['packages'] ?? null)) {
            throw new RuntimeException("Malformed lockfile: {$lockPath}");
        }

        $packages = [];
        foreach ($lock['packages'] as $row) {
            if (!is_array($row) || !is_string($row['name'] ?? null) || !is_string($row['version'] ?? null)) {
                throw new RuntimeException("Malformed package entry in {$lockPath}");
            }
            $type = $row['type'] ?? null;
            if (!is_string($type) || !isset(self::KINDS[$type])) {
                continue;
            }
            $dist = $row['dist'] ?? null;
            $packages[] = [
                'name' => $row['name'],
                'key' => self::KINDS[$type] . '/' . substr($row['name'], (int) strpos($row['name'], '/') + 1),
                'version' => ltrim($row['version'], 'v'),
                'firstParty' => is_array($dist) && ($dist['type'] ?? null) === 'path',
            ];
        }

        return $packages;
    }
}
