<?php

namespace Gebruederheitz\GutenbergBlocks;

use Gebruederheitz\GutenbergBlocks\Helper\Yaml;
use Gebruederheitz\SimpleSingleton\Singleton;
use WP_Block_Editor_Context;

class BlockRegistrar extends Singleton
{
    /**
     * @hook ghwp-register-dynamic-blocks
     * @description Add blocks to have them registered.
     */
    const HOOK_REGISTER_DYNAMIC_BLOCKS = 'ghwp-register-dynamic-blocks';

    /**
     * @hook ghwp-allowed-gutenberg-blocks
     * @description Filters the blocks shown to editors in Gutenberg.
     */
    const HOOK_ALLOWED_BLOCKS = 'ghwp-allowed-gutenberg-blocks';

    /**
     * @hook ghwp-allowed-widget-blocks
     * @description Filters the blocks shown to editors in widget editing areas.
     */
    const HOOK_ALLOWED_WIDGET_BLOCKS = 'ghwp-allowed-widget-blocks';

    /**
     * @hook ghwp-script-localization-data
     * @description Filters the data provided to the editor frontend via script
     *              localization.
     */
    const HOOK_SCRIPT_LOCALIZATION_DATA = 'ghwp-script-localization-data';

    /**
     * @var string The handle for the editor script file.
     */
    protected string $scriptHandle = 'ghwp-gutenberg-blocks';

    /**
     * @var string The path to the editor script file relative to the theme root
     */
    protected string $scriptPath = '/js/backend.js';

    /**
     * @var array<string>|string|true An array of allowed block names or the
     *          path to a yaml file – or true to allow all block types.
     */
    protected string|array|true $customAllowedBlocks = [];

    /**
     * Returns the current theme version as read from the style.css.
     */
    public static function getThemeVersion(): string
    {
        return wp_get_theme()->get('Version');
    }

    protected function __construct()
    {
        parent::__construct();

        add_action('init', [$this, 'onInit']);
        add_action('admin_init', [$this, 'onAdminInit']);
    }

    public function setScriptPath(string $scriptPath): self
    {
        $this->scriptPath = $scriptPath;

        return $this;
    }

    public function setScriptHandle(string $scriptHandle): self
    {
        $this->scriptHandle = $scriptHandle;

        return $this;
    }

    /**
     * @param true|string|array<string>|null $customAllowedBlocks
     */
    public function setAllowedBlocks(array|true|string $customAllowedBlocks = null): self
    {
        $this->customAllowedBlocks = $customAllowedBlocks ?: [];

        return $this;
    }

    /**
     * Callback for the 'init' action hook.
     */
    public function onInit(): void
    {
        $this->registerDynamicBlocks();
    }

    /**
     * Callback for the 'admin_init' action hook.
     */
    public function onAdminInit(): void
    {
        $this->registerBlockScripts();
    }

    /**
     * Callback for the 'allowed_block_types_all' filter hook, returning an
     * array of allowed core & custom block types shown to the editor.
     *
     * @param bool|string[] $allowedBlockTypes Array of block type slugs, or boolean to enable/disable all.
     *                                         Default true (all registered block types supported).
     * @return string[]|bool
     */
    public function onAllowedBlockTypes(
        array|bool $allowedBlockTypes,
        WP_Block_Editor_Context $context
    ): array | bool {
        return $this->getAllowedBlockTypes($context);
    }

    /**
     * @return string[]|bool
     *
     * Also handles blocks allowed in widget areas / sidebars:
     *   https://github.com/WordPress/gutenberg/issues/28517#issuecomment-1070239810
     */
    public function getAllowedBlockTypes(
        ?WP_Block_Editor_Context $context = null
    ): array | bool {
        if (
            $context !== null &&
            in_array($context->name, [
                'core/edit-widgets',
                'core/customize-widgets',
            ])
        ) {
            if (is_string($this->customAllowedBlocks)) {
                $widgetAllowedBlocks = Yaml::read(
                    filename: $this->customAllowedBlocks,
                    key: 'widgetsAllowedBlocks',
                );
            }

            return apply_filters(
                self::HOOK_ALLOWED_WIDGET_BLOCKS,
                $widgetAllowedBlocks ?? [],
            );
        }

        $allowedBlocks = [];

        if (is_array($this->customAllowedBlocks)) {
            $allowedBlocks = $this->customAllowedBlocks;
        } elseif (is_string($this->customAllowedBlocks)) {
            $allowedBlocks = Yaml::read(
                filename: $this->customAllowedBlocks,
                key: 'gutenbergAllowedBlocks',
            );
        } elseif ($this->customAllowedBlocks === true) {
            return true;
        }

        return apply_filters(self::HOOK_ALLOWED_BLOCKS, $allowedBlocks);
    }

    /**
     * Registers the custom gutenberg blocks and sets the data they require;
     * restricts the block types shown to the user
     */
    protected function registerBlockScripts(): void
    {
        add_filter(
            'allowed_block_types_all',
            [$this, 'onAllowedBlockTypes'],
            10,
            2,
        );
        wp_register_script(
            $this->scriptHandle,
            get_template_directory_uri() . $this->scriptPath,
            [
                'wp-blocks',
                'wp-element',
                'wp-editor',
                'wp-data',
                'wp-components',
                'wp-compose',
                'wp-i18n',
                'wp-edit-post',
                'wp-plugins',
            ],
            self::getThemeVersion(),
        );

        /*
         * Make PHP-only data available to the blocks JS via localization;
         * the fields of the array are available as the global variable
         * `editorData`.
         */
        $localizationData = apply_filters(
            self::HOOK_SCRIPT_LOCALIZATION_DATA,
            [],
        );
        wp_localize_script(
            $this->scriptHandle,
            'editorData',
            $localizationData,
        );

        register_block_type('ghwp/blocks', [
            'editor_script' => $this->scriptHandle,
        ]);
    }

    protected function registerDynamicBlocks(): void
    {
        $blocks = [];

        $blocks = apply_filters(self::HOOK_REGISTER_DYNAMIC_BLOCKS, $blocks);

        foreach ($blocks as $block) {
            $this->registerDynamicBlock($block);
        }
    }

    protected function registerDynamicBlock(DynamicBlock $block): void
    {
        if ($block->getCustomScripts()) {
            foreach ($block->getCustomScripts() as $handle => $path) {
                wp_register_script(
                    $handle,
                    get_template_directory_uri() . $path,
                    [
                        'wp-blocks',
                        'wp-element',
                        'wp-editor',
                        'wp-data',
                        'wp-components',
                        'wp-compose',
                        'wp-i18n',
                        'wp-edit-post',
                        'wp-plugins',
                    ],
                    self::getThemeVersion(),
                );

                register_block_type($block->getName(), [
                    'editor_script' => $handle,
                    'render_callback' => [$block, 'renderBlock'],
                    'attributes' => $block->getAttributes(),
                ]);
            }
        } else {
            // Default route, using the global script bundle
            register_block_type($block->getName(), [
                'editor_script' => $this->scriptHandle,
                'render_callback' => [$block, 'renderBlock'],
                'attributes' => $block->getAttributes(),
            ]);
        }

        $customStylesheets = $block->getCustomStylesheets();
        if ($customStylesheets) {
            foreach ($customStylesheets as $handle => $path) {
                wp_register_style(
                    $handle,
                    get_template_directory_uri() . $path,
                );
                register_block_style($block->getName(), [
                    'name' => $handle,
                    'label' => 'Block Styles for ' . $block->getName(),
                    'is_default' => true,
                    'style_handle' => $handle,
                ]);
            }
        }
    }
}
