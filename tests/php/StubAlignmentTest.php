<?php

declare(strict_types=1);

namespace LockedStack\Tests;

use LockedStack\ReleaseChecks\StubAlignment;
use PHPUnit\Framework\TestCase;

final class StubAlignmentTest extends TestCase
{
    private const array ALIGNED = [
        'wp-plugin/woocommerce' => '11.2.2',
        'php-stubs/woocommerce-stubs' => '11.2.0',
        'php-stubs/wordpress-stubs' => '7.1.2',
    ];

    public function testPatchDifferencesAreAccepted(): void
    {
        self::assertSame([], StubAlignment::check(self::ALIGNED, '7.1.4'));
    }

    public function testWooCommerceStubsBehindByAMinorAreRejected(): void
    {
        $locked = ['php-stubs/woocommerce-stubs' => '11.1.9'] + self::ALIGNED;

        self::assertSame(
            ['WooCommerce 11.2.2 is analysed against php-stubs/woocommerce-stubs 11.1.9'],
            StubAlignment::check($locked, '7.1.4'),
        );
    }

    public function testWordPressStubsBehindByAMinorAreRejected(): void
    {
        self::assertSame(
            ['WordPress 7.2.0 is analysed against php-stubs/wordpress-stubs 7.1.2'],
            StubAlignment::check(self::ALIGNED, '7.2.0'),
        );
    }

    public function testMissingStubPackageIsRejected(): void
    {
        $locked = self::ALIGNED;
        unset($locked['php-stubs/wordpress-stubs']);

        self::assertCount(1, StubAlignment::check($locked, '7.1.4'));
    }
}
