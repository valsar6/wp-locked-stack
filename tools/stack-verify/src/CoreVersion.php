<?php

declare(strict_types=1);

namespace LockedStack\Verify;

final class CoreVersion
{
    public static function read(string $root): ?string
    {
        $content = @file_get_contents($root . '/wp-includes/version.php');
        if ($content === false || preg_match('/\$wp_version\s*=\s*\'([^\']+)\'/', $content, $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }
}
