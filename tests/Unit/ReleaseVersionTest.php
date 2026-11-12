<?php

namespace Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ReleaseVersionTest extends TestCase
{
    public function testPatchVersionAdvancesWhenTheSourceCommitChanges(): void
    {
        $version = pos_release_version_for_commit(
            'new-commit',
            ['version' => '13.3', 'source_commit' => 'old-commit'],
            '13.3'
        );

        $this->assertSame('13.3.1', $version);
    }

    public function testPatchVersionStaysStableForThePackagedCommit(): void
    {
        $version = pos_release_version_for_commit(
            'same-commit',
            ['version' => '13.3.4', 'source_commit' => 'same-commit'],
            '13.3'
        );

        $this->assertSame('13.3.4', $version);
    }

    public function testNewerReleaseTagTakesPrecedenceOverAutomaticPatchIncrement(): void
    {
        $version = pos_release_version_for_commit(
            'new-commit',
            ['version' => '13.3.4', 'source_commit' => 'old-commit'],
            'v13.4.0'
        );

        $this->assertSame('13.4.0', $version);
    }

    public function testPatchVersionCanAdvancePastSingleDigitPatches(): void
    {
        $this->assertSame('13.3.10', pos_increment_patch_version('v13.3.9'));
    }

    public function testPatchVersionRejectsNonSemanticVersions(): void
    {
        $this->expectException(InvalidArgumentException::class);

        pos_increment_patch_version('nightly');
    }
}
