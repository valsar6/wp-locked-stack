<?php

declare(strict_types=1);

use LockedStack\Verify\ManifestBuilder;

require_once __DIR__ . '/../src/autoload.php';

$arguments = is_array($_SERVER['argv'] ?? null) ? array_values(array_filter($_SERVER['argv'], 'is_string')) : [];
if (count($arguments) !== 4) {
    fwrite(STDERR, "Usage: build-manifest.php <composer.lock> <wordpress-root> <output>\n");
    exit(2);
}
[, $lockPath, $root, $output] = $arguments;

try {
    $manifest = ManifestBuilder::build($lockPath, $root);
} catch (RuntimeException $exception) {
    fwrite(STDERR, $exception->getMessage() . "\n");
    exit(1);
}

file_put_contents($output, $manifest->toJson());
