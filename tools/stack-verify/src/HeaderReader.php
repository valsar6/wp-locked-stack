<?php

declare(strict_types=1);

namespace LockedStack\Verify;

final class HeaderReader
{
    private const int HEADER_BYTES = 8192;

    public static function pluginVersion(string $path): ?string
    {
        $candidates = is_dir($path) ? (glob($path . '/*.php') ?: []) : [$path];
        foreach ($candidates as $file) {
            $header = self::head($file);
            if (preg_match('/^[ \t\/*#@]*Plugin Name:/mi', $header) === 1) {
                return self::version($header);
            }
        }

        return null;
    }

    public static function themeVersion(string $path): ?string
    {
        $stylesheet = $path . '/style.css';

        return is_file($stylesheet) ? self::version(self::head($stylesheet)) : null;
    }

    private static function version(string $header): ?string
    {
        if (preg_match('/^[ \t\/*#@]*Version:(.*)$/mi', $header, $matches) !== 1) {
            return null;
        }
        $version = trim($matches[1]);

        return $version === '' ? null : $version;
    }

    private static function head(string $file): string
    {
        $content = file_get_contents($file, false, null, 0, self::HEADER_BYTES);

        return $content === false ? '' : $content;
    }
}
