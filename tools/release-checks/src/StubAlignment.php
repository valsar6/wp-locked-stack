<?php

declare(strict_types=1);

namespace LockedStack\ReleaseChecks;

final class StubAlignment
{
    private const string WOOCOMMERCE = 'wp-plugin/woocommerce';
    private const string WOOCOMMERCE_STUBS = 'php-stubs/woocommerce-stubs';
    private const string WORDPRESS_STUBS = 'php-stubs/wordpress-stubs';

    /**
     * @param array<string, string> $locked
     * @return list<string>
     */
    public static function check(array $locked, string $coreVersion): array
    {
        return array_values(array_filter([
            self::compare('WooCommerce', $locked[self::WOOCOMMERCE] ?? null, self::WOOCOMMERCE_STUBS, $locked),
            self::compare('WordPress', $coreVersion, self::WORDPRESS_STUBS, $locked),
        ]));
    }

    /**
     * @param array<string, string> $locked
     */
    private static function compare(string $label, ?string $runtime, string $stubPackage, array $locked): ?string
    {
        $stubs = $locked[$stubPackage] ?? null;
        if ($runtime === null || $stubs === null) {
            return sprintf('%s: cannot compare runtime %s with %s %s', $label, $runtime ?? 'unknown', $stubPackage, $stubs ?? 'unknown');
        }

        return self::minor($runtime) === self::minor($stubs)
            ? null
            : "{$label} {$runtime} is analysed against {$stubPackage} {$stubs}";
    }

    private static function minor(string $version): string
    {
        return implode('.', array_slice(explode('.', ltrim($version, 'v')), 0, 2));
    }
}
