<?php

declare(strict_types=1);

namespace Gebruederheitz\GutenbergBlocks\Tests\Unit;

use Gebruederheitz\GutenbergBlocks\BlockRegistrar;
use Gebruederheitz\GutenbergBlocks\Tests\Support\TestCase;
use Gebruederheitz\GutenbergBlocks\Tests\Support\WpEnv;
use PHPUnit\Framework\Attributes\CoversClass;
use WP_Block_Editor_Context;

#[CoversClass(BlockRegistrar::class)]
class BlockRegistrarTest extends TestCase
{
    private BlockRegistrar $registrar;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registrar = BlockRegistrar::getInstance();
    }

    public function testDefaultsToAnEmptyAllowList(): void
    {
        $this->registrar->setAllowedBlocks([]);

        self::assertSame([], $this->registrar->getAllowedBlockTypes());
    }

    public function testReturnsAnExplicitArrayOfAllowedBlocks(): void
    {
        $this->registrar->setAllowedBlocks(['core/paragraph', 'ghwp/teaser']);

        self::assertSame(['core/paragraph', 'ghwp/teaser'], $this->registrar->getAllowedBlockTypes());
    }

    public function testTrueAllowsEveryBlockType(): void
    {
        $this->registrar->setAllowedBlocks(true);

        self::assertTrue($this->registrar->getAllowedBlockTypes());
    }

    public function testReadsAllowedBlocksFromAYamlFile(): void
    {
        $this->registrar->setAllowedBlocks($this->fixture('blocks.yaml'));

        self::assertSame(['core/paragraph', 'ghwp/teaser'], $this->registrar->getAllowedBlockTypes());
    }

    public function testUsesWidgetAllowListWithinWidgetContexts(): void
    {
        $this->registrar->setAllowedBlocks($this->fixture('blocks.yaml'));
        $context = new WP_Block_Editor_Context('core/edit-widgets');

        self::assertSame(['core/legacy-widget'], $this->registrar->getAllowedBlockTypes($context));
    }

    public function testAllowedBlocksAreFilterable(): void
    {
        $this->registrar->setAllowedBlocks(['core/paragraph']);
        WpEnv::addFilter(
            BlockRegistrar::HOOK_ALLOWED_BLOCKS,
            static fn (array $blocks): array => [...$blocks, 'ghwp/injected'],
        );

        self::assertSame(['core/paragraph', 'ghwp/injected'], $this->registrar->getAllowedBlockTypes());
    }

    public function testSetterAccepts(): void
    {
        self::assertSame($this->registrar, $this->registrar->setScriptHandle('custom-handle'));
        self::assertSame($this->registrar, $this->registrar->setScriptPath('/js/custom.js'));
        self::assertSame($this->registrar, $this->registrar->setAllowedBlocks(null));
        self::assertSame($this->registrar, $this->registrar->addWordpressEditorScriptDependencies(['wp-blob']));
    }

    public function testGetThemeVersionReadsFromTheActiveTheme(): void
    {
        WpEnv::$themeVersion = '4.2.0';

        self::assertSame('4.2.0', BlockRegistrar::getThemeVersion());
    }
}
