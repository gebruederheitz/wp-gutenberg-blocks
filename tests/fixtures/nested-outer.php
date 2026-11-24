<?php
/**
 * Test fixture for nested renders: prints the query vars it can see, renders
 * nested-inner.php through PartialRenderer (empty content, no className) and
 * prints its own query vars again afterwards.
 */

use Gebruederheitz\GutenbergBlocks\PartialRenderer;

$describe = static fn(): string => sprintf(
    'innerBlocks=%s className=%s title=%s',
    get_query_var('innerBlocks'),
    get_query_var('className'),
    get_query_var('title'),
);

echo 'outer before: ' . $describe() . "\n";
echo PartialRenderer::render(__DIR__ . '/nested-inner.php', ['title' => 'inner']);
echo 'outer after: ' . $describe() . "\n";
