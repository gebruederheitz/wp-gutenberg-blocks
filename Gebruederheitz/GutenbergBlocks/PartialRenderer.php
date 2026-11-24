<?php

namespace Gebruederheitz\GutenbergBlocks;

class PartialRenderer
{
    /**
     * Renders a template part.
     *
     * Every data key, `innerBlocks` (the inner content) and `className` are
     * set as query vars for the template and restored to their previous
     * values afterwards, so a template can itself render further partials
     * without leaking its own values into them or losing them.
     *
     * @param string $templatePath The template partial's full path
     * @param array<string, mixed> $data Data you want to provide to the template via
     *                                   query parameters
     *
     * @return false|string          Rendered content
     */
    public static function render(
        string $templatePath,
        array $data = [],
        string $content = '',
        string|null $overridePath = null,
    ): false|string {
        $queryVars = $data;
        $queryVars['innerBlocks'] = !empty($content)
            ? $content
            : $data['innerBlocks'] ?? null;
        $queryVars['className'] = $data['className'] ?? null;

        $previousQueryVars = [];
        foreach ($queryVars as $name => $value) {
            $previousQueryVars[$name] = get_query_var($name, null);
            set_query_var($name, $value);
        }

        if (isset($data['postId'])) {
            $post = get_post($data['postId']);
        }
        if (isset($post) || isset($data['post'])) {
            $post = $post ?? $data['post'];
            setup_postdata($post);
        }

        $templatePathUsed = $templatePath;

        if (
            isset($overridePath) &&
            ($overriddenTemplate = locate_template($overridePath))
        ) {
            $templatePathUsed = $overriddenTemplate;
        }

        ob_start();
        load_template($templatePathUsed, false, $data);
        $content = ob_get_contents();
        ob_end_clean();

        wp_reset_postdata();
        foreach ($previousQueryVars as $name => $value) {
            set_query_var($name, $value);
        }

        return $content;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function renderInclude(
        string $templatePath,
        array $data = [],
        string|null $overridePath = null,
    ): void {
        $templatePathUsed = $templatePath;

        if (
            isset($overridePath) &&
            ($overriddenTemplate = locate_template($overridePath))
        ) {
            $templatePathUsed = $overriddenTemplate;
        }

        load_template($templatePathUsed, false, $data);
    }
}
