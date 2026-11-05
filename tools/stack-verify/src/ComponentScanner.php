<?php

declare(strict_types=1);

namespace LockedStack\Verify;

final class ComponentScanner
{
    private const array LOCATIONS = [
        'plugin' => 'plugins',
        'theme' => 'themes',
        'mu-plugin' => 'mu-plugins',
    ];

    private const array IGNORED_NAMES = ['index.php'];

    /**
     * @return array<string, Component>
     */
    public static function scan(string $root): array
    {
        $components = [];
        foreach (self::LOCATIONS as $kind => $directory) {
            $base = "{$root}/wp-content/{$directory}";
            foreach (is_dir($base) ? (scandir($base) ?: []) : [] as $name) {
                if (str_starts_with($name, '.') || in_array($name, self::IGNORED_NAMES, true)) {
                    continue;
                }
                $path = "{$base}/{$name}";
                $key = "{$kind}/{$name}";
                $components[$key] = new Component(
                    $key,
                    $kind === 'theme' ? HeaderReader::themeVersion($path) : HeaderReader::pluginVersion($path),
                    TreeHasher::hash($path),
                );
            }
        }

        return $components;
    }
}
