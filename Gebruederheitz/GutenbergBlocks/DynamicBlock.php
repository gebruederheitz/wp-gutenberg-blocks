<?php

namespace Gebruederheitz\GutenbergBlocks;

use function apply_filters;
use function add_filter;

class DynamicBlock
{
    /**
     * @hook ghwp-dynamic-block-attributes
     * @description Filter the block attributes before rendering, with the block
     *              type as the first and the array of attributes as the
     *              second parameter.
     */
    public const HOOK_FILTER_BLOCK_ATTRIBUTES = 'ghwp-dynamic-block-attributes';

    /**
     * @hook ghwp-dynamic-block-attributes-${TYPE}
     * @description Like "ghwp-dynamic-block-attributes", but specific for each
     *              block type.
     */
    public const HOOK_FILTER_BLOCK_TYPE_ATTRIBUTES = 'ghwp-dynamic-block-attributes-';

    /** @var string The block's name. This needs to match the name used when registering the block type in JS. */
    protected $name = '';

    /** @var string The full path to the partial to be used for rendering the block */
    protected $partial = '';

    /** @var array<string, mixed> The attributes provided to the renderer with their optional defaults */
    protected $attributes = [];

    /** @var array<string> Attributes that may not be empty for successful rendering to proceed */
    protected $requiredAttributes = [];

    /** @var ?string The path where a theme may override the template used; provide the string as you would use it in get_template_part() */
    protected $templateOverridePath = null;

    /** @var ?array<string, string> */
    protected $customScripts = null;

    /** @var ?array<string, string> */
    protected $customStylesheets = null;

    /**
     * @param ?array<string, mixed> $attributes
     * @param ?array<string> $requiredAttributes
     * @param ?string $templateOverridePath
     */
    public static function make(
        string $name,
        string $partial,
        array $attributes = null,
        array $requiredAttributes = null,
        string $templateOverridePath = null
    ): DynamicBlock {
        return new DynamicBlock(
            $name,
            $partial,
            $attributes,
            $requiredAttributes,
            $templateOverridePath,
        );
    }

    /**
     * DynamicBlock constructor.
     *
     * @param ?array<string, mixed> $attributes
     * @param ?array<string> $requiredAttributes
     */
    public function __construct(
        string $name,
        string $partial,
        array $attributes = null,
        array $requiredAttributes = null,
        string $templateOverridePath = null
    ) {
        $this->name = $name;
        $this->partial = $partial;

        if (isset($attributes)) {
            $this->attributes = $attributes;
        }

        if (isset($requiredAttributes)) {
            $this->requiredAttributes = $requiredAttributes;
        }

        if (isset($templateOverridePath)) {
            $this->templateOverridePath = $templateOverridePath;
        }
    }

    public function register(): void
    {
        add_filter(BlockRegistrar::HOOK_REGISTER_DYNAMIC_BLOCKS, function (
            $blocks
        ) {
            $blocks[] = $this;
            return $blocks;
        });

        add_filter(BlockRegistrar::HOOK_ALLOWED_BLOCKS, function (
            $allowedBlocks
        ) {
            $allowedBlocks[] = $this->name;
            return $allowedBlocks;
        });
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): DynamicBlock
    {
        $this->name = $name;

        return $this;
    }

    public function getPartial(): string
    {
        return $this->partial;
    }

    public function setPartial(string $partial): DynamicBlock
    {
        $this->partial = $partial;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function setAttributes(array $attributes): DynamicBlock
    {
        $this->attributes = $attributes;

        return $this;
    }

    /**
     * @return array<string>
     */
    public function getRequiredAttributes(): array
    {
        return $this->requiredAttributes;
    }

    /**
     * @param array<string> $requiredAttributes
     */
    public function setRequiredAttributes(
        array $requiredAttributes
    ): DynamicBlock {
        $this->requiredAttributes = $requiredAttributes;

        return $this;
    }

    /**
     * @return ?string
     */
    public function getTemplateOverridePath(): ?string
    {
        return $this->templateOverridePath;
    }

    /**
     * @param string $templateOverridePath
     *
     * @return DynamicBlock
     */
    public function setTemplateOverridePath(
        string $templateOverridePath
    ): DynamicBlock {
        $this->templateOverridePath = $templateOverridePath;

        return $this;
    }

    /**
     * @return array<string, string>|null
     */
    public function getCustomScripts(): ?array
    {
        return $this->customScripts;
    }

    /**
     * @param array<string, string>|null $customScripts
     */
    public function setCustomScripts(?array $customScripts): DynamicBlock
    {
        $this->customScripts = $customScripts;
        return $this;
    }

    public function addCustomScript(string $handle, string $path): DynamicBlock
    {
        $this->customScripts[$handle] = $path;

        return $this;
    }

    /**
     * @return array<string, string>|null
     */
    public function getCustomStylesheets(): ?array
    {
        return $this->customStylesheets;
    }

    /**
     * @param array<string, string>|null $customStylesheets
     */
    public function setCustomStylesheets(
        ?array $customStylesheets
    ): DynamicBlock {
        $this->customStylesheets = $customStylesheets;

        return $this;
    }

    public function addCustomStylesheet(
        string $handle,
        string $path
    ): DynamicBlock {
        $this->customStylesheets[$handle] = $path;

        return $this;
    }

    /**
     * Checks whether all required attributes are present (not empty) in the
     * array of attributes passed.
     *
     * @param array<string, mixed> $attributes The attributes passed by the block
     */
    protected function requiredAttributesArePresent(array $attributes): bool
    {
        foreach ($this->requiredAttributes as $requiredAttribute) {
            if (empty($attributes[$requiredAttribute])) {
                return false;
            }
        }
        return true;
    }

    /**
     * Callback for the block registration handler, which will render the block
     * with the attributes provided.
     *
     * @param array<string, mixed> $attributes
     *
     * @return false|string|null
     */
    public function renderBlock(array $attributes = [], string $content = '')
    {
        $attributes = apply_filters(
            self::HOOK_FILTER_BLOCK_ATTRIBUTES,
            $attributes,
        );
        $attributes = apply_filters(
            self::HOOK_FILTER_BLOCK_TYPE_ATTRIBUTES . $this->name,
            $attributes,
        );

        if (!$this->requiredAttributesArePresent($attributes)) {
            return null;
        }

        return PartialRenderer::render(
            $this->partial,
            $attributes,
            $content,
            $this->templateOverridePath,
        );
    }
}
