<?php

declare(strict_types=1);

namespace Gebruederheitz\GutenbergBlocks\Tests\Unit;

use Gebruederheitz\GutenbergBlocks\Helper\Yaml;
use Gebruederheitz\GutenbergBlocks\Tests\Support\TestCase;
use Gebruederheitz\GutenbergBlocks\Tests\Support\WpEnv;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Yaml::class)]
class YamlTest extends TestCase
{
    public function testReadsAnAbsoluteYamlFile(): void
    {
        $result = Yaml::read($this->fixture('blocks.yaml'));

        self::assertSame(['core/paragraph', 'ghwp/teaser'], $result['gutenbergAllowedBlocks']);
    }

    public function testExtractsASingleKey(): void
    {
        $result = Yaml::read($this->fixture('blocks.yaml'), key: 'widgetsAllowedBlocks');

        self::assertSame(['core/legacy-widget'], $result);
    }

    public function testReturnsDefaultWhenFileIsMissing(): void
    {
        $result = Yaml::read('/does/not/exist.yaml', default: ['fallback']);

        self::assertSame(['fallback'], $result);
    }

    public function testReturnsDefaultWhenKeyIsAbsent(): void
    {
        $result = Yaml::read(
            $this->fixture('blocks.yaml'),
            default: ['fallback'],
            key: 'missingKey',
        );

        self::assertSame(['fallback'], $result);
    }

    public function testFallsBackToThemeRootForRelativePaths(): void
    {
        WpEnv::$themeRoot = self::FIXTURES;

        $result = Yaml::read('/blocks.yaml', key: 'gutenbergAllowedBlocks');

        self::assertSame(['core/paragraph', 'ghwp/teaser'], $result);
    }
}
