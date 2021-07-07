# Wordpress Gutenberg Blocks Helper

_Helps you get your blocks on the road_

---

Helps you with registering and rendering your custom Gutenberg blocks and acts 
as a common interface for libraries providing additional blocks.

# Installation

via composer:
```shell
> composer require gebruederheitz/wp-gutenberg-blocks
```

Make sure you have Composer autoload or an alternative class loader present.

# Usage

## Initializing the block registrar

Initialize the registrar (usually in your `functions.php`):

```php
<?php

use Gebruederheitz\GutenbergBlocks\BlockRegistrar;

new BlockRegistrar();
```

You may pass an alternative handle and path of your editor script to the 
constructor:

```php
<?php

use Gebruederheitz\GutenbergBlocks\BlockRegistrar;

new BlockRegistrar(null, '/scripts/gutenberg.js', 'my-gutenberg-blocks');
```

The script handle defaults to `ghwp-gutenberg-blocks`, the script path to
`/js/backend.js`. The script path is relative to the theme root
(`get_template_directory_uri()`).


### Setting allowed blocks

There are three ways of setting the blocks shown to the user in the editor. If
you want to skip all that and simply allow all block types, pass `true` as the 
first parameter to the registrar's constructor:

```php
new BlockRegistrar(true);
```

#### Dynamically through an array

You can also **provide a list of allowed blocks via an array** (it defaults to an
empty array, initially allowing no blocks whatsoever):

```php
new BlockRegistrar(['core/columns', 'core/column', 'core/paragraph']);
```

#### Using a configuration file

Alternatively, you may use a **YAML configuration** file:
```yaml
# wp-content/themes/my-theme/config/example.yaml
gutenbergAllowedBlocks:
  - core/columns
  - core/column
  - core/paragraph
```

The value needs to be an array of strings and on the top level under the key
`gutenbergAllowedBlocks`. You can then pass the file's path (relative to the
themes root as returned by `get_theme_root()` **or** as an absolute filesystem
path) to the registrar's constructor as a string:

```php
new BlockRegistrar('/my-theme/config/example.yaml');
```


#### Even more dynamically through the filter hook

The third option is to use the filter hook to add allowed blocks (this is what
the `DynamicBlock` class uses to automatically set up its availability):

```php
use Gebruederheitz\GutenbergBlocks\BlockRegistrar;

function allowCustomBlock(array $allowedBlocks): array {
    $allowedBlocks[] = 'my/block';
    return $allowedBlocks;
}

add_filter(BlockRegistrar::HOOK_ALLOWED_BLOCKS, 'allowCustomBlock');
```


## Registering a dynamic block

This all assumes you have defined the editor component, attributes etc. in your
editor script and registered the block there using `wp.blocks.registerBlockType()`.
Your `save` component returns `null` – and this is where you want to register a
dynamic block that is rendered by PHP.

It is possible to allow a theme to override your default template partial for 
the block (even if your default partial file is outside the theme source 
directory) through the fifth parameter.

```php
# functions.php or your block component library (or anywhere, really, but called on every request)
use Gebruederheitz\GutenbergBlocks\DynamicBlock;
use Gebruederheitz\GutenbergBlocks\BlockRegistrar;

new BlockRegistrar();

$myblock = new DynamicBlock(
    // Required: Block name needs to match the name the block was registered with in JS
    'namespace/block-name',
    // Required: Absolute path to the template partial rendering the block
    dirname(__FILE__) . '/templates/my-block.php', 
    // List of block attributes with type and default value.
    // You don't need to provide all attributes, only those that should receive
    // default values. Defaults to [].
    [                               
        'attributeName' => [
            'type' => 'string',
            'default' => 'default value',
        ],       
    ],
    // List of required attributes. If any of these are not set, the block will 
    // not be rendered. Make sure not to provide a default value for these
    // attributes! Defaults to [].
    [
        'requiredAttributeName',
    ],
    // A path to allow a (child) theme to override the default template used for
    // rendering the block (as provided by the second parameter). This allows a 
    // theme to render modified markup or different classnames for the same
    // block. The path needs to be relative to the theme's root, as you would 
    // use it in get_template_part(). Defaults to null (theme can not override
    // the default template partial).
    'template-parts/blocks/block-name.php'
);

// Set up the hook listeners to automagically register & render the block
$myblock->register();
```

## Available Hooks

| Class constant   | hook handle  | type | description |
| ---              | --- | --- | ---|
| `BlockRegistrar::` `HOOK_REGISTER_DYNAMIC_BLOCKS` | ghwp-register-dynamic-blocks | filter | Extend the provided array with an instance of `DynamicBlock` to automagically register that block. `DynamicBlock` does this for you when you call `register()`. |
| `BlockRegistrar::` `HOOK_ALLOWED_BLOCKS` | ghwp-allowed-gutenberg-blocks | filter | A proxy for WP's own `allowed_block_types`. |
| `BlockRegistrar::` `HOOK_SCRIPT_LOCALIZATION_DATA` | ghwp-script-localization-data | filter | Add items that your block requires to the localization data for the editor script. |

# Development

> todo
