<?php

declare(strict_types=1);

namespace LockedStack\Tests;

use LockedStack\Verify\Drift;
use LockedStack\Verify\DriftKind;
use LockedStack\Verify\Manifest;
use LockedStack\Verify\ManifestBuilder;
use LockedStack\Verify\Verifier;
use PHPUnit\Framework\TestCase;

final class VerifierTest extends TestCase
{
    private SiteTree $site;
    private Manifest $manifest;

    protected function setUp(): void
    {
        $this->site = new SiteTree();
        $this->manifest = ManifestBuilder::build($this->site->writeLock(), $this->site->root);
    }

    protected function tearDown(): void
    {
        $this->site->destroy();
    }

    public function testCleanTreeHasNoDrift(): void
    {
        self::assertSame([], Verifier::verify($this->manifest, $this->site->root));
    }

    public function testManifestSurvivesSerialization(): void
    {
        $path = "{$this->site->root}/manifest.json";
        file_put_contents($path, $this->manifest->toJson());

        self::assertSame([], Verifier::verify(Manifest::fromFile($path), $this->site->root));
    }

    public function testEditedFileIsContentMismatch(): void
    {
        $this->site->write('wp-content/plugins/sample/extra.php', "<?php\n");

        $this->assertSingleDrift(DriftKind::ContentMismatch, 'plugin/sample');
    }

    public function testChangedVersionHeaderIsVersionMismatch(): void
    {
        $this->site->writePlugin('sample', '1.0.0');

        $this->assertSingleDrift(DriftKind::VersionMismatch, 'plugin/sample');
    }

    public function testUnlistedPluginIsUnexpected(): void
    {
        $this->site->writePlugin('sideloaded', '9.9.9');

        $this->assertSingleDrift(DriftKind::Unexpected, 'plugin/sideloaded');
    }

    public function testUnlistedMustUsePluginIsUnexpected(): void
    {
        $this->site->write('wp-content/mu-plugins/hotfix.php', "<?php\n");

        $this->assertSingleDrift(DriftKind::Unexpected, 'mu-plugin/hotfix.php');
    }

    public function testRemovedPluginIsMissing(): void
    {
        unlink("{$this->site->root}/wp-content/plugins/sample/sample.php");
        rmdir("{$this->site->root}/wp-content/plugins/sample");

        $this->assertSingleDrift(DriftKind::Missing, 'plugin/sample');
    }

    public function testChangedCoreIsCoreVersionDrift(): void
    {
        $this->site->write('wp-includes/version.php', "<?php\n\$wp_version = '7.0.0';\n");

        $this->assertSingleDrift(DriftKind::CoreVersion, 'wordpress');
    }

    public function testStockIndexFileIsIgnored(): void
    {
        $this->site->write('wp-content/plugins/index.php', "<?php\n");

        self::assertSame([], Verifier::verify($this->manifest, $this->site->root));
    }

    private function assertSingleDrift(DriftKind $kind, string $subject): void
    {
        $drifts = Verifier::verify($this->manifest, $this->site->root);

        self::assertSame(
            [[$kind, $subject]],
            array_map(static fn (Drift $drift): array => [$drift->kind, $drift->subject], $drifts),
        );
    }
}
