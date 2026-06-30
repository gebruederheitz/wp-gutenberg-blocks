<?php

declare(strict_types=1);

namespace Gebruederheitz\GutenbergBlocks\Tests\Support;

use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected const FIXTURES = __DIR__ . '/../fixtures';

    protected function setUp(): void
    {
        parent::setUp();
        WpEnv::reset();
    }

    protected function fixture(string $relativePath): string
    {
        return self::FIXTURES . '/' . ltrim($relativePath, '/');
    }
}
