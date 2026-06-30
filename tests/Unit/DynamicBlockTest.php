<?php

declare(strict_types=1);

namespace Gebruederheitz\GutenbergBlocks\Tests\Unit;

use Gebruederheitz\GutenbergBlocks\BlockRegistrar;
use Gebruederheitz\GutenbergBlocks\DynamicBlock;
use Gebruederheitz\GutenbergBlocks\Tests\Support\TestCase;
use Gebruederheitz\GutenbergBlocks\Tests\Support\WpEnv;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(DynamicBlock::class)]
class DynamicBlockTest extends TestCase
{
    public function testMakeAppliesNameAndPartialWithSaneDefaults(): void
    {
        $block = DynamicBlock::make('ghwp/teaser', '/partials/teaser.php');

        self::assertSame('ghwp/teaser', $block->getName());
        self::assertSame('/partials/teaser.php', $block->getPartial());
        self::assertSame([], $block->getAttributes());
        self::assertSame([], $block->getRequiredAttributes());
        self::assertNull($block->getTemplateOverridePath());
        self::assertNull($block->getCustomScripts());
    }

    public function testSettersAreFluentAndPersistValues(): void
    {
        $block = DynamicBlock::make('ghwp/teaser', '/partial.php');

        $returned = $block
            ->setName('ghwp/hero')
            ->setAttributes(['title' => ['type' => 'string']])
            ->setRequiredAttributes(['title'])
            ->setTemplateOverridePath('blocks/hero.php')
            ->addCustomScript('hero-js', '/js/hero.js')
            ->addCustomStylesheet('hero-css', '/css/hero.css');

        self::assertSame($block, $returned);
        self::assertSame('ghwp/hero', $block->getName());
        self::assertSame(['title' => ['type' => 'string']], $block->getAttributes());
        self::assertSame(['title'], $block->getRequiredAttributes());
        self::assertSame('blocks/hero.php', $block->getTemplateOverridePath());
        self::assertSame(['hero-js' => '/js/hero.js'], $block->getCustomScripts());
        self::assertSame(['hero-css' => '/css/hero.css'], $block->getCustomStylesheets());
    }

    public function testRegisterHooksTheBlockIntoRegistrarFilters(): void
    {
        $block = DynamicBlock::make('ghwp/teaser', '/partial.php');
        $block->register();

        $blocks = apply_filters(BlockRegistrar::HOOK_REGISTER_DYNAMIC_BLOCKS, []);
        $allowed = apply_filters(BlockRegistrar::HOOK_ALLOWED_BLOCKS, []);

        self::assertSame([$block], $blocks);
        self::assertSame(['ghwp/teaser'], $allowed);
    }

    public function testRenderBlockReturnsNullWhenRequiredAttributesAreMissing(): void
    {
        $block = DynamicBlock::make('ghwp/teaser', $this->fixture('partial.php'))
            ->setRequiredAttributes(['greeting']);

        self::assertNull($block->renderBlock(['greeting' => '']));
    }

    public function testRenderBlockRendersThePartialWithAttributes(): void
    {
        $block = DynamicBlock::make('ghwp/teaser', $this->fixture('partial.php'))
            ->setRequiredAttributes(['greeting']);

        self::assertSame('partial:hello', $block->renderBlock(['greeting' => 'hello']));
    }

    public function testRenderBlockAppliesAttributeFiltersBeforeValidation(): void
    {
        $block = DynamicBlock::make('ghwp/teaser', $this->fixture('partial.php'))
            ->setRequiredAttributes(['greeting']);

        // A type-specific filter supplies the otherwise-missing required value.
        WpEnv::addFilter(
            DynamicBlock::HOOK_FILTER_BLOCK_TYPE_ATTRIBUTES . 'ghwp/teaser',
            static function (array $attributes): array {
                $attributes['greeting'] = 'filtered';

                return $attributes;
            },
        );

        self::assertSame('partial:filtered', $block->renderBlock([]));
    }
}
