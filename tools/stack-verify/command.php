<?php

declare(strict_types=1);

use LockedStack\Verify\Manifest;
use LockedStack\Verify\Verifier;

if (!class_exists('WP_CLI')) {
    return;
}

require_once __DIR__ . '/src/autoload.php';

WP_CLI::add_command(
    'stack verify',
    /**
     * @param list<string> $args
     * @param array<string, mixed> $assoc
     */
    static function (array $args, array $assoc): void {
        $manifest = Manifest::fromFile(is_string($assoc['manifest'] ?? null) ? $assoc['manifest'] : '/opt/stack/manifest.json');
        $root = is_string($assoc['root'] ?? null) ? $assoc['root'] : (string) getcwd();
        $drifts = Verifier::verify($manifest, $root);

        foreach ($drifts as $drift) {
            WP_CLI::log($drift->describe());
        }
        if ($drifts !== []) {
            WP_CLI::error(sprintf('%d drift(s) found against the build manifest.', count($drifts)));
        }

        WP_CLI::success(sprintf(
            'WordPress %s and %d components match the build manifest.',
            $manifest->coreVersion,
            count($manifest->entries),
        ));
    },
    [
        'when' => 'before_wp_load',
        'shortdesc' => 'Compares the running tree with the manifest written at image build time.',
        'synopsis' => [
            ['type' => 'assoc', 'name' => 'manifest', 'optional' => true],
            ['type' => 'assoc', 'name' => 'root', 'optional' => true],
        ],
    ],
);
