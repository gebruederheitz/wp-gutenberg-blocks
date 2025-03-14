<?php

namespace Gebruederheitz\GutenbergBlocks;

class PartialRenderer
{
    /**
     * Renders a template part.
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
        string $overridePath = null
    ) {
        foreach ($data as $name => $datum) {
            set_query_var($name, $datum);
        }
        if (isset($data['postId'])) {
            $post = get_post($data['postId']);
        }
        if (isset($post) || isset($data['post'])) {
            $post = $post ?? $data['post'];
            setup_postdata($post);
        }
        if (!empty($content)) {
            set_query_var('innerBlocks', $content);
        }
        if (!empty($data['className'])) {
            set_query_var('className', $data['className']);
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
        foreach ($data as $name => $datum) {
            set_query_var($name, null);
        }
        set_query_var('innerBlocks', null);
        set_query_var('className', null);

        return $content;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function renderInclude(
        string $templatePath,
        array $data = [],
        string $overridePath = null
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
