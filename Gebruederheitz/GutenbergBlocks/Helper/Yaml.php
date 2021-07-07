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
    public static function read(string $filename, $default = [], string $key = null)
    {
        if (!file_exists($filename)) {
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
}
