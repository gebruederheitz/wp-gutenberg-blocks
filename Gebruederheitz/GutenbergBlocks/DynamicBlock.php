<?php

namespace Gebruederheitz\GutenbergBlocks;

class DynamicBlock
{
    /** @var string The block's name. This needs to match the name used when registering the block type in JS. */
    protected $name = '';

    /** @var string The full path to the partial to be used for rendering the block */
    protected $partial = '';

    /** @var array The attributes provided to the renderer with their optional defaults */
    protected $attributes = [];

    /** @var array Attributes that may not be empty for successful rendering to proceed */
    protected $requiredAttributes = [];

    /** @var ?string The path where a theme may override the template used; provide the string as you would use it in get_template_part() */
    protected $templateOverridePath = null;

    /**
     * DynamicBlock constructor.
     *
     * @param string  $name
     * @param string  $partial
     * @param ?array  $attributes
     * @param ?array  $requiredAttributes
     */
    public function __construct(
        string $name,
        string $partial,
        array $attributes = null,
        array $requiredAttributes = null,
        string $templateOverridePath = null
    ) {
        $this->name               = $name;
        $this->partial            = $partial;

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

    public function register()
    {
         add_filter(BlockRegistrar::HOOK_REGISTER_DYNAMIC_BLOCKS, function($blocks) {
            $blocks[] = $this;
            return $blocks;
        });

        add_filter(BlockRegistrar::HOOK_ALLOWED_BLOCKS, function($allowedBlocks) {
            $allowedBlocks[] = $this->name;
            return $allowedBlocks;
        });
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return DynamicBlock
     */
    public function setName(string $name): DynamicBlock
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getPartial(): string
    {
        return $this->partial;
    }

    /**
     * @param string $partial
     *
     * @return DynamicBlock
     */
    public function setPartial(string $partial): DynamicBlock
    {
        $this->partial = $partial;

        return $this;
    }

    /**
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @param array $attributes
     *
     * @return DynamicBlock
     */
    public function setAttributes(array $attributes): DynamicBlock
    {
        $this->attributes = $attributes;

        return $this;
    }

    /**
     * @return array
     */
    public function getRequiredAttributes(): array
    {
        return $this->requiredAttributes;
    }

    /**
     * @param array $requiredAttributes
     *
     * @return DynamicBlock
     */
    public function setRequiredAttributes(array $requiredAttributes
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
    public function setTemplateOverridePath(string $templateOverridePath): DynamicBlock
    {
        $this->templateOverridePath = $templateOverridePath;

        return $this;
    }

    /**
     * Checks whether all required attributes are present (not empty) in the
     * array of attributes passed.
     *
     * @param array $attributes The attributes passed by the block
     *
     * @return bool
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
     * @param array $attributes
     *
     * @return false|string|null
     */
    public function renderBlock(array $attributes = [])
    {
        if (!$this->requiredAttributesArePresent($attributes)) {
            return null;
        }

        return PartialRenderer::render($this->partial, $attributes, $this->templateOverridePath);
    }
}
