<?php

declare(strict_types=1);

use LockedStack\ReleaseChecks\PackageState;
use LockedStack\ReleaseChecks\StubAlignment;
use LockedStack\Verify\HeaderReader;

require_once __DIR__ . '/../src/StubAlignment.php';
require_once __DIR__ . '/../src/PackageState.php';
require_once __DIR__ . '/../../stack-verify/src/HeaderReader.php';

/**
 * @return array<mixed>
 */
function read_json(string $path): array
{
    $decoded = json_decode((string) file_get_contents($path), true, 64, JSON_THROW_ON_ERROR);

    return is_array($decoded) ? $decoded : [];
}

/**
 * @return array<string, string>
 */
function locked_versions(string $lockPath): array
{
    $lock = read_json($lockPath);
    $versions = [];
    foreach (['packages', 'packages-dev'] as $section) {
        $rows = $lock[$section] ?? [];
        foreach (is_array($rows) ? $rows : [] as $row) {
            if (is_array($row) && is_string($row['name'] ?? null) && is_string($row['version'] ?? null)) {
                $versions[$row['name']] = ltrim($row['version'], 'v');
            }
        }
    }

    return $versions;
}

function core_version(string $dockerfile): string
{
    $matched = preg_match('/^FROM wordpress:(\d+\.\d+\.\d+)/m', (string) file_get_contents($dockerfile), $matches);

    return $matched === 1 ? $matches[1] : 'unknown';
}

function git_output(string $command): string
{
    return trim((string) shell_exec($command . ' 2>/dev/null'));
}

$gitStatus = 0;
exec('git rev-parse --is-inside-work-tree 2>/dev/null', result_code: $gitStatus);
if ($gitStatus !== 0) {
    fwrite(STDERR, "Cannot read git history, so released versions cannot be checked.\n");
    exit(1);
}

$locked = locked_versions('composer.lock');
$problems = StubAlignment::check($locked, core_version('Dockerfile'));

foreach (glob('packages/*', GLOB_ONLYDIR) ?: [] as $directory) {
    $slug = basename($directory);
    $manifest = read_json("{$directory}/composer.json");
    $name = is_string($manifest['name'] ?? null) ? $manifest['name'] : $slug;
    $declared = is_string($manifest['version'] ?? null) ? $manifest['version'] : 'unknown';
    $header = ($manifest['type'] ?? null) === 'wordpress-theme'
        ? HeaderReader::themeVersion($directory)
        : HeaderReader::pluginVersion($directory);

    $tag = strtok(git_output('git tag --list ' . escapeshellarg("{$slug}/v*") . ' --sort=-v:refname'), "\n");
    $changed = false;
    if ($tag !== false) {
        $status = 0;
        exec('git diff --quiet ' . escapeshellarg($tag) . ' -- ' . escapeshellarg($directory), result_code: $status);
        $changed = $status !== 0;
    }
    $released = $tag !== false ? substr($tag, strlen("{$slug}/v")) : null;

    $state = new PackageState($name, $declared, $header, $locked[$name] ?? null, $released, $changed);
    $problems = [...$problems, ...$state->problems()];
}

foreach ($problems as $problem) {
    fwrite(STDERR, "{$problem}\n");
}
if ($problems !== []) {
    exit(1);
}

echo "Package versions, tags and stubs are aligned.\n";
