<?php

declare(strict_types=1);

namespace LockedStack\Tests;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class SiteTree
{
    public readonly string $root;

    public function __construct()
    {
        $this->root = sys_get_temp_dir() . '/stack-' . bin2hex(random_bytes(6));
        $this->write('wp-includes/version.php', "<?php\n\$wp_version = '7.1.4';\n");
        $this->writePlugin('sample', '1.1.0');
        $this->write('wp-content/themes/sample-theme/style.css', "/*\nTheme Name: Sample\nVersion: 1.0.0\n*/\n");
    }

    public function writePlugin(string $slug, string $version): void
    {
        $this->write("wp-content/plugins/{$slug}/{$slug}.php", "<?php\n/**\n * Plugin Name: {$slug}\n * Version: {$version}\n */\n");
    }

    public function write(string $relativePath, string $content): void
    {
        $path = "{$this->root}/{$relativePath}";
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
        file_put_contents($path, $content);
    }

    public function writeLock(string $pluginVersion = '1.1.0'): string
    {
        $lock = [
            'packages' => [
                [
                    'name' => 'acme/sample',
                    'version' => $pluginVersion,
                    'type' => 'wordpress-plugin',
                    'dist' => ['type' => 'path'],
                ],
                [
                    'name' => 'acme/sample-theme',
                    'version' => '1.0.0',
                    'type' => 'wordpress-theme',
                    'dist' => ['type' => 'zip'],
                ],
                [
                    'name' => 'composer/installers',
                    'version' => 'v2.3.0',
                    'type' => 'composer-plugin',
                ],
            ],
        ];
        $path = "{$this->root}/composer.lock";
        file_put_contents($path, json_encode($lock, JSON_THROW_ON_ERROR));

        return $path;
    }

    public function destroy(): void
    {
        $flags = FilesystemIterator::SKIP_DOTS | FilesystemIterator::CURRENT_AS_FILEINFO;
        /** @var iterable<SplFileInfo> $entries */
        $entries = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->root, $flags),
            RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($entries as $entry) {
            $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
        }
        rmdir($this->root);
    }
}
