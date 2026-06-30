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
}
