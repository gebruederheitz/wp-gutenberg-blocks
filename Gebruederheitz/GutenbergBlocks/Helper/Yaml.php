<?php

namespace Gebruederheitz\GutenbergBlocks\Helper;

use Exception;
use Symfony\Component\Yaml\Yaml as SymfonyYaml;

class Yaml
{
    /**
     * Parses a YAML file and returns its content. In case of failure returns
     * $default.
     *
     * @param  string $filename The path to the YAML file (relative to the themes root or absolute filesystem path)
     * @param  mixed  $default A fallback default to return if reading fails.
     *
     * @return array|mixed
     */
    public static function read(
        string $filename,
        $default = [],
        string $key = null
    ) {
        if (self::isDirectoryRestricted($filename) || !file_exists($filename)) {
            $filename = get_theme_root() . $filename;
            if (!file_exists($filename)) {
                return $default;
            }
        }

        try {
            $yaml = SymfonyYaml::parseFile($filename);
        } catch (Exception $e) {
            $yaml = $default;
        }

        if (isset($key)) {
            if (array_key_exists($key, $yaml)) {
                $yaml = $yaml[$key];
            } else {
                return $default;
            }
        }

        return $yaml;
    }

    protected static function isDirectoryRestricted(string $dir): bool
    {
        // Default error handler is required
        \set_error_handler(null);

        // Clean last error info. You can do it using error_clean_last in PHP 7.
        @trigger_error('__clean_error_info');

        // Testing...
        @file_exists($dir);

        // Restore previous error handler
        \restore_error_handler();

        // Return `true` if error has occured
        return ($error = error_get_last()) &&
            $error['message'] !== '__clean_error_info';
    }
}
