<?php

namespace Gebruederheitz\GutenbergBlocks;

class PartialRenderer
{
    /**
     * Renders a template part.
     *
     * @param string $templatePath The template partial's full path
     * @param array  $data         Data you want to provide to the template via
     *                                 query parameters in the format
     *                                 [string] parameterName => [mixed] data
     *
     * @return false|string          Rendered content
     */
    public static function render(string $templatePath, array $data = [], string $overridePath = null)
    {
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

        $templatePathUsed = $templatePath;

        if (isset($overridePath) && $overriddenTemplate = locate_template($overridePath)) {
            $templatePathUsed = $overriddenTemplate;
        }

        ob_start();
        load_template($templatePathUsed, false, $data);
        $content = ob_get_contents();
        ob_end_clean();

        wp_reset_postdata();

        return $content;
    }
}
