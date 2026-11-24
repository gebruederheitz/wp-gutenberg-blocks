<?php

declare(strict_types=1);

namespace Gebruederheitz\GutenbergBlocks\Tests\Unit;

use Gebruederheitz\GutenbergBlocks\PartialRenderer;
use Gebruederheitz\GutenbergBlocks\Tests\Support\TestCase;
use Gebruederheitz\GutenbergBlocks\Tests\Support\WpEnv;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(PartialRenderer::class)]
class PartialRendererTest extends TestCase
{
    public function testRenderReturnsBufferedTemplateOutput(): void
    {
        $output = PartialRenderer::render($this->fixture('partial.php'), ['greeting' => 'hi']);

        self::assertSame('partial:hi', $output);
    }

    public function testRenderResetsQueryVarsAndPostDataAfterwards(): void
    {
        PartialRenderer::render($this->fixture('partial.php'), ['greeting' => 'hi']);

        self::assertNull(WpEnv::$queryVars['greeting']);
        self::assertNull(WpEnv::$queryVars['innerBlocks']);
        self::assertNull(WpEnv::$queryVars['className']);
        self::assertCount(1, WpEnv::callsTo('wp_reset_postdata'));
    }

    public function testNestedRenderDoesNotSeeTheOuterRendersInnerBlocksOrClassName(): void
    {
        $output = $this->renderNested();

        self::assertStringContainsString("inner: innerBlocks= className= title=inner\n", $output);
    }

    public function testNestedRenderRestoresTheOuterRendersQueryVars(): void
    {
        $output = $this->renderNested();

        $expected = 'innerBlocks=<p>outer content</p> className=outer-class title=outer';
        self::assertStringContainsString("outer before: {$expected}\n", $output);
        self::assertStringContainsString("outer after: {$expected}\n", $output);
    }

    public function testRenderRestoresAPreviouslySetQueryVar(): void
    {
        WpEnv::$queryVars['s'] = 'search term';

        PartialRenderer::render($this->fixture('partial.php'), ['s' => 'from block data']);

        self::assertSame('search term', WpEnv::$queryVars['s']);
    }

    public function testRenderRestoresPreviouslySetInnerBlocksAndClassName(): void
    {
        WpEnv::$queryVars['innerBlocks'] = '<p>outer content</p>';
        WpEnv::$queryVars['className'] = 'outer-class';

        PartialRenderer::render($this->fixture('partial.php'), ['className' => 'inner-class'], '<p>inner</p>');

        self::assertSame('<p>outer content</p>', WpEnv::$queryVars['innerBlocks']);
        self::assertSame('outer-class', WpEnv::$queryVars['className']);
    }

    public function testRenderUsesThemeOverrideWhenLocatable(): void
    {
        WpEnv::$locatedTemplates['blocks/override.php'] = $this->fixture('override.php');

        $output = PartialRenderer::render(
            $this->fixture('partial.php'),
            [],
            '',
            'blocks/override.php',
        );

        self::assertSame('override', $output);
    }

    public function testRenderIncludeLoadsTheTemplateDirectly(): void
    {
        ob_start();
        PartialRenderer::renderInclude($this->fixture('partial.php'), ['greeting' => 'direct']);
        $output = ob_get_clean();

        self::assertSame('partial:direct', $output);
    }

    private function renderNested(): string
    {
        return (string) PartialRenderer::render(
            $this->fixture('nested-outer.php'),
            ['title' => 'outer', 'className' => 'outer-class'],
            '<p>outer content</p>',
        );
    }
}
