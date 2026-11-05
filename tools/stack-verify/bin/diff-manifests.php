<?php

declare(strict_types=1);

use LockedStack\Verify\Manifest;
use LockedStack\Verify\ManifestDiff;

require_once __DIR__ . '/../src/autoload.php';

$arguments = is_array($_SERVER['argv'] ?? null) ? array_values(array_filter($_SERVER['argv'], 'is_string')) : [];
if (count($arguments) !== 3) {
    fwrite(STDERR, "Usage: diff-manifests.php <previous> <next>\n");
    exit(2);
}
[, $previous, $next] = $arguments;

try {
    $changes = ManifestDiff::versionChanges(Manifest::fromFile($previous), Manifest::fromFile($next));
} catch (RuntimeException | JsonException $exception) {
    fwrite(STDERR, $exception->getMessage() . "\n");
    exit(1);
}

foreach ($changes as $change) {
    echo $change, "\n";
}
