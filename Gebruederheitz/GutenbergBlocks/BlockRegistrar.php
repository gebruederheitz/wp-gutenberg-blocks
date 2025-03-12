<?php

namespace Gebruederheitz\GutenbergBlocks;

use Gebruederheitz\GutenbergBlocks\Helper\Yaml;
use Gebruederheitz\SimpleSingleton\Singleton;

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
     * @hook ghwp-script-localization-data
     * @description Filters the data provided to the editor frontend via script
     *              localization.
     */
    const HOOK_SCRIPT_LOCALIZATION_DATA = 'ghwp-script-localization-data';

    /**
     * @var string The handle for the editor script file.
     */
    protected $scriptHandle = 'ghwp-gutenberg-blocks';

    /**
     * @var string The path to the editor script file relative to the theme root
     */
    protected $scriptPath = '/js/backend.js';

    /**
     * @var array<string>|string|true An array of allowed block names or the
     *                                 path to a yaml file – or true to allow
     *                                 all block types.
     */
    protected $customAllowedBlocks = [];

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
     * @param array<string>|string|true|null $customAllowedBlocks
     */
    public function setAllowedBlocks($customAllowedBlocks = null): self
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
     * @return string[]|bool
     */
    public function onAllowedBlockTypes()
    {
        return $this->getAllowedBlockTypes();
    }

    /**
     * @return string[]|boolean
     */
    public function getAllowedBlockTypes()
    {
        if (!empty($this->filteredAllowedBlocks)) {
            return $this->filteredAllowedBlocks;
        }

        $allowedBlocks = [];

        if (is_array($this->customAllowedBlocks)) {
            $allowedBlocks = $this->customAllowedBlocks;
        } elseif (is_string($this->customAllowedBlocks)) {
            $allowedBlocks = Yaml::read(
                $this->customAllowedBlocks,
                [],
                'gutenbergAllowedBlocks',
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
        add_filter('allowed_block_types_all', [$this, 'onAllowedBlockTypes']);
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
        register_block_type($block->getName(), [
            'editor_script' => $this->scriptHandle,
            'render_callback' => [$block, 'renderBlock'],
            'attributes' => $block->getAttributes(),
        ]);
    }
}
