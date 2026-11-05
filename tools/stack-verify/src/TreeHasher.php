<?php

declare(strict_types=1);

namespace LockedStack\Verify;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

final class TreeHasher
{
    public static function hash(string $path): string
    {
        if (is_file($path)) {
            return self::hashFile($path);
        }

        $flags = FilesystemIterator::SKIP_DOTS | FilesystemIterator::CURRENT_AS_FILEINFO;
        /** @var iterable<SplFileInfo> $files */
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, $flags));

        $lines = [];
        foreach ($files as $file) {
            $relative = substr($file->getPathname(), strlen($path) + 1);
            $lines[$relative] = $file->isLink()
                ? 'link:' . $file->getLinkTarget()
                : self::hashFile($file->getPathname());
        }
        ksort($lines, SORT_STRING);

        $context = hash_init('sha256');
        foreach ($lines as $relative => $digest) {
            hash_update($context, "{$relative}\0{$digest}\n");
        }

        return hash_final($context);
    }

    private static function hashFile(string $path): string
    {
        $digest = hash_file('sha256', $path);
        if ($digest === false) {
            throw new RuntimeException("Cannot hash file: {$path}");
        }

        return $digest;
    }
}
