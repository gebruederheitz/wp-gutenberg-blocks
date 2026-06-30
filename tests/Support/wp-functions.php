<?php

/**
 * Minimal global WordPress stubs, backed by {@see WpEnv}. Each is guarded with
 * function_exists() so the suite still loads if it is ever run inside a real
 * WordPress test environment. Add a new stub here whenever the library starts
 * calling another WP function.
 */

declare(strict_types=1);

use Gebruederheitz\GutenbergBlocks\Tests\Support\WpEnv;

if (!class_exists('WP_Block_Editor_Context')) {
    // phpcs:ignore -- intentional global test double for the WP core class
    class WP_Block_Editor_Context
    {
        public function __construct(public ?string $name = null)
        {
        }
    }
}

if (!function_exists('add_filter')) {
    function add_filter(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1): bool
    {
        WpEnv::addFilter($hook, $callback, $priority);

        return true;
    }
}

if (!function_exists('apply_filters')) {
    function apply_filters(string $hook, mixed $value, mixed ...$args): mixed
    {
        return WpEnv::applyFilters($hook, $value, $args);
    }
}

if (!function_exists('add_action')) {
    function add_action(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1): bool
    {
        WpEnv::$actions[$hook][] = $callback;

        return true;
    }
}

if (!function_exists('get_theme_root')) {
    function get_theme_root(): string
    {
        return WpEnv::$themeRoot;
    }
}

if (!function_exists('set_query_var')) {
    function set_query_var(string $key, mixed $value): void
    {
        WpEnv::$queryVars[$key] = $value;
    }
}

if (!function_exists('get_query_var')) {
    function get_query_var(string $key, mixed $default = ''): mixed
    {
        return WpEnv::$queryVars[$key] ?? $default;
    }
}

if (!function_exists('get_post')) {
    function get_post(mixed $id = null): mixed
    {
        return WpEnv::$posts[$id] ?? null;
    }
}

if (!function_exists('setup_postdata')) {
    function setup_postdata(mixed $post): bool
    {
        WpEnv::record('setup_postdata', [$post]);

        return true;
    }
}

if (!function_exists('wp_reset_postdata')) {
    function wp_reset_postdata(): void
    {
        WpEnv::record('wp_reset_postdata', []);
    }
}

if (!function_exists('locate_template')) {
    function locate_template(string|array $templateNames, bool $load = false, bool $requireOnce = true): string
    {
        $key = is_array($templateNames) ? implode('|', $templateNames) : $templateNames;

        return WpEnv::$locatedTemplates[$key] ?? '';
    }
}

if (!function_exists('load_template')) {
    function load_template(string $templateFile, bool $requireOnce = true, array $args = []): void
    {
        WpEnv::record('load_template', [$templateFile, $requireOnce, $args]);

        if (is_file($templateFile)) {
            extract($args, EXTR_SKIP);
            include $templateFile;
        }
    }
}

if (!function_exists('get_template_directory_uri')) {
    function get_template_directory_uri(): string
    {
        return WpEnv::$templateDirectoryUri;
    }
}

if (!function_exists('wp_get_theme')) {
    function wp_get_theme(mixed $stylesheet = null): object
    {
        return new class {
            public function get(string $header): string
            {
                return $header === 'Version' ? WpEnv::$themeVersion : '';
            }
        };
    }
}

if (!function_exists('wp_register_script')) {
    function wp_register_script(string $handle, string $src, array $deps = [], mixed $ver = false): bool
    {
        WpEnv::record('wp_register_script', [$handle, $src, $deps, $ver]);

        return true;
    }
}

if (!function_exists('wp_localize_script')) {
    function wp_localize_script(string $handle, string $objectName, array $data): bool
    {
        WpEnv::record('wp_localize_script', [$handle, $objectName, $data]);

        return true;
    }
}

if (!function_exists('wp_register_style')) {
    function wp_register_style(string $handle, string $src, array $deps = [], mixed $ver = false): bool
    {
        WpEnv::record('wp_register_style', [$handle, $src, $deps, $ver]);

        return true;
    }
}

if (!function_exists('register_block_type')) {
    function register_block_type(string $name, array $args = []): object
    {
        WpEnv::record('register_block_type', [$name, $args]);

        return (object) ['name' => $name];
    }
}

if (!function_exists('register_block_style')) {
    function register_block_style(string $blockName, array $styleProperties): bool
    {
        WpEnv::record('register_block_style', [$blockName, $styleProperties]);

        return true;
    }
}
