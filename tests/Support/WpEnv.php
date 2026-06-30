<?php

declare(strict_types=1);

namespace Gebruederheitz\GutenbergBlocks\Tests\Support;

/**
 * Lightweight in-memory stand-in for the slice of WordPress the library
 * touches. The global wp_* functions in wp-functions.php delegate here, so
 * tests can drive and inspect WordPress interactions without bootstrapping a
 * real WP install.
 *
 * Call {@see WpEnv::reset()} from every test's setUp() to get a clean slate.
 */
final class WpEnv
{
    /** @var array<string, array<int, list<callable>>> hook => priority => callbacks */
    public static array $filters = [];

    /** @var array<string, list<callable>> action hook => callbacks */
    public static array $actions = [];

    /** @var array<string, mixed> */
    public static array $queryVars = [];

    /** @var array<int|string, mixed> post id => post */
    public static array $posts = [];

    /** @var array<string, string> requested template path => resolved override path */
    public static array $locatedTemplates = [];

    /** @var array<string, list<array<int, mixed>>> function name => recorded call args */
    public static array $calls = [];

    public static string $themeRoot = '';

    public static string $themeVersion = '1.0.0';

    public static string $templateDirectoryUri = 'https://example.test/wp-content/themes/demo';

    public static function reset(): void
    {
        self::$filters = [];
        self::$actions = [];
        self::$queryVars = [];
        self::$posts = [];
        self::$locatedTemplates = [];
        self::$calls = [];
        self::$themeRoot = '';
        self::$themeVersion = '1.0.0';
    }

    /**
     * @param array<int, mixed> $args
     */
    public static function record(string $function, array $args): void
    {
        self::$calls[$function][] = $args;
    }

    /**
     * @return list<array<int, mixed>>
     */
    public static function callsTo(string $function): array
    {
        return self::$calls[$function] ?? [];
    }

    public static function addFilter(string $hook, callable $callback, int $priority = 10): void
    {
        self::$filters[$hook][$priority][] = $callback;
    }

    /**
     * @param array<int, mixed> $args
     */
    public static function applyFilters(string $hook, mixed $value, array $args = []): mixed
    {
        foreach (self::sortedByPriority(self::$filters[$hook] ?? []) as $callback) {
            $value = $callback($value, ...$args);
        }

        return $value;
    }

    /**
     * @param array<int, list<callable>> $byPriority
     * @return list<callable>
     */
    private static function sortedByPriority(array $byPriority): array
    {
        ksort($byPriority);

        return array_merge(...array_values($byPriority) ?: [[]]);
    }
}
