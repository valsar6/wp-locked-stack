<?php

declare(strict_types=1);

namespace LockedStack\Tests;

use LockedStack\Verify\ManifestBuilder;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ManifestBuilderTest extends TestCase
{
    private SiteTree $site;

    protected function setUp(): void
    {
        $this->site = new SiteTree();
    }

    protected function tearDown(): void
    {
        $this->site->destroy();
    }

    public function testRecordsOnlyWordPressPackages(): void
    {
        $manifest = ManifestBuilder::build($this->site->writeLock(), $this->site->root);

        self::assertSame('7.1.4', $manifest->coreVersion);
        self::assertSame(['plugin/sample', 'theme/sample-theme'], array_keys($manifest->entries));
    }

    public function testRejectsFirstPartyHeaderThatDisagreesWithLock(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('plugin/sample declares version 1.1.0 but the lockfile says 1.2.0');

        ManifestBuilder::build($this->site->writeLock('1.2.0'), $this->site->root);
    }

    public function testRejectsCodeThatIsNotInTheLock(): void
    {
        $this->site->writePlugin('sideloaded', '9.9.9');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Not in the lockfile: plugin/sideloaded');

        ManifestBuilder::build($this->site->writeLock(), $this->site->root);
    }

    public function testRejectsLockedPackageThatIsNotInTheTree(): void
    {
        unlink("{$this->site->root}/wp-content/themes/sample-theme/style.css");
        rmdir("{$this->site->root}/wp-content/themes/sample-theme");

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('acme/sample-theme is locked but theme/sample-theme is not in the tree');

        ManifestBuilder::build($this->site->writeLock(), $this->site->root);
    }
}
