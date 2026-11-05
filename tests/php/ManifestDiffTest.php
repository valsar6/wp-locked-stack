<?php

declare(strict_types=1);

namespace LockedStack\Tests;

use LockedStack\Verify\Component;
use LockedStack\Verify\Manifest;
use LockedStack\Verify\ManifestDiff;
use LockedStack\Verify\ManifestEntry;
use PHPUnit\Framework\TestCase;

final class ManifestDiffTest extends TestCase
{
    public function testContentOnlyChangeIsNotAVersionChange(): void
    {
        $previous = self::manifest('7.1.4', ['plugin/shop' => ['1.0.0', 'aaa']]);
        $next = self::manifest('7.1.4', ['plugin/shop' => ['1.0.0', 'bbb']]);

        self::assertSame([], ManifestDiff::versionChanges($previous, $next));
    }

    public function testReportsCoreAndPluginUpgrades(): void
    {
        $previous = self::manifest('7.1.4', ['plugin/shop' => ['1.0.0', 'aaa']]);
        $next = self::manifest('7.1.5', ['plugin/shop' => ['1.1.0', 'bbb']]);

        self::assertSame(
            ['wordpress 7.1.4 -> 7.1.5', 'plugin/shop 1.0.0 -> 1.1.0'],
            ManifestDiff::versionChanges($previous, $next),
        );
    }

    public function testReportsAddedAndRemovedComponents(): void
    {
        $previous = self::manifest('7.1.4', ['plugin/old' => ['1.0.0', 'aaa']]);
        $next = self::manifest('7.1.4', ['plugin/new' => ['2.0.0', 'bbb']]);

        self::assertSame(
            ['plugin/new added at 2.0.0', 'plugin/old removed'],
            ManifestDiff::versionChanges($previous, $next),
        );
    }

    /**
     * @param array<string, array{string, string}> $components
     */
    private static function manifest(string $core, array $components): Manifest
    {
        $entries = [];
        foreach ($components as $key => [$version, $hash]) {
            $entries[$key] = new ManifestEntry("acme/{$key}", $version, new Component($key, $version, $hash));
        }

        return new Manifest($core, $entries);
    }
}
