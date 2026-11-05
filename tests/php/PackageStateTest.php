<?php

declare(strict_types=1);

namespace LockedStack\Tests;

use LockedStack\ReleaseChecks\PackageState;
use PHPUnit\Framework\TestCase;

final class PackageStateTest extends TestCase
{
    public function testFirstReleaseHasNoTagToCompare(): void
    {
        self::assertSame([], (new PackageState('acme/a', '1.0.0', '1.0.0', '1.0.0', null, false))->problems());
    }

    public function testUnchangedReleaseIsClean(): void
    {
        self::assertSame([], (new PackageState('acme/a', '1.0.0', '1.0.0', '1.0.0', '1.0.0', false))->problems());
    }

    public function testChangeWithBumpIsClean(): void
    {
        self::assertSame([], (new PackageState('acme/a', '1.1.0', '1.1.0', '1.1.0', '1.0.0', true))->problems());
    }

    public function testChangeWithoutBumpIsRejected(): void
    {
        self::assertSame(
            ['acme/a: code changed since release 1.0.0, bump the version'],
            (new PackageState('acme/a', '1.0.0', '1.0.0', '1.0.0', '1.0.0', true))->problems(),
        );
    }

    public function testVersionOlderThanReleaseIsRejected(): void
    {
        self::assertSame(
            ['acme/a: 1.0.0 is older than released 1.1.0'],
            (new PackageState('acme/a', '1.0.0', '1.0.0', '1.0.0', '1.1.0', false))->problems(),
        );
    }

    public function testHeaderAndLockMustMatchComposerJson(): void
    {
        self::assertSame(
            [
                'acme/a: header says 1.0.0, composer.json says 1.1.0',
                'acme/a: composer.lock says 1.0.0, composer.json says 1.1.0',
            ],
            (new PackageState('acme/a', '1.1.0', '1.0.0', '1.0.0', null, false))->problems(),
        );
    }
}
