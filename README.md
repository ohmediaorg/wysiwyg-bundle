# Overview

This bundle offers a form field that allows certain HTML tags and Twig syntax,
while filtering out anything else.

## Installation

Update `composer.json` by adding this to the `repositories` array:

```json
{
    "type": "vcs",
    "url": "https://github.com/ohmediaorg/wysiwyg-bundle"
}
```

Then run `composer require ohmediaorg/wysiwyg-bundle:dev-main`.

Import the routes in `config/routes.yaml`:

```yaml
oh_media_wysiwyg:
    resource: '@OHMediaWysiwygBundle/config/routes.yaml'
```

Run `npm install tinymce`

Create the minimum config file in `config/oh_media_wysiwyg.yaml`:

```yaml
oh_media_wysiwyg:
    tags:
    tinymce:
```

This will enable/disable HTML tags based on constant values inside of
`OHMedia\WysiwygBundle\Util\HtmlTags`.

You can also specify your own preferences:

```yaml
oh_media_wysiwyg:
    tags:
        fieldset: true
        table: false
    tinymce:
```

The available options under in `tinymce` are `plugins`, `menu`, and `toolbar`.
The values of these options should mimic the value passed into the tinymce.init
function.

## Form Field

Add the field to your form:

```php
use OHMedia\WysiwygBundle\Form\Type\WysiwygType;

$builder->add('description', WysiwygType::class);
```

You can also specify allowed tags per form field:

```php
use OHMedia\WysiwygBundle\Form\Type\WysiwygType;

$builder->add('description', WysiwygType::class, [
    'allowed_tags' => ['p', 'div', 'span'],
]);
```

You will need to apply your preferred WYSIWYG editor to the field manually.

## Twig Functions

You can define simple twig functions for use in the Wysiwyg field content. For
the most part, these functions should have at most a singular integer parameter.
No variables, filters, etc. This keeps the syntax simple for the average user.

Create an extension as usual, but extend
`OHMedia\WysiwygBundle\Twig\AbstractWysiwygExtension`:

```php
namespace App\Twig;

use OHMedia\WysiwygBundle\Twig\AbstractWysiwygExtension;
use Twig\TwigFunction;

class WysiwygExtension extends AbstractWysiwygExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('my_custom_function', [$this, 'myCustomFunction']),
        ];
    }
}
```

You will only be able to define the `getFunctions` function, but every Twig
function you define this way can be used in a Wysiwyg form field.

**IMPORTANT: if your function will result in further calls to the Wysiwyg render
function, you NEED TO implement something to prevent infinite recursion.**

```php
private int $renders = 0;

public function myCustomFunction()
{
    if ($this->renders > 5) {
        return '';
    }

    $this->renders++;

    // ...
}
```

## Rendering

The Wysiwyg form value will be saved with the HTML tags and Twig syntax filtered
out. You will need to render it after the fact using the service:

```php
use OHMedia\WysiwygBundle\Service\Wysiwyg;

public function myControllerAction(Wysiwyg $wysiwyg)
{
    $description = $myEntity->getDescription();

    $rendered = $wysiwyg->render($description);
}
```

or the Twig function:

```twig
{{ wysiwyg(my_entity.description) }}
```

If you overwrote the `allowed_tags` in the form field, you will need to pass
that same array as the second parameter of the render function.

You can do this in PHP:

```php
$rendered = $wysiwyg->render($description, ['p', 'div', 'span']);
```

or in Twig:

```twig
{{ wysiwyg(my_entity.description, ['p', 'div', 'span']) }}
```

## Prevent Entity Deletion

You may want to prevent certain Entities from being deleted if a corresponding
"shortcode" is in use.

A Repository can implement `OHMedia\WysiwygBundle\Repository\WysiwygRepositoryInterface`
to check for fields containing the shortcodes. This would be for any DB value
that could contain a shortcode that is rendered with `{{ wysiwyg(value) }}`.

```php
public function containsWysiwygShortcodes(string ...$shortcodes): bool
{
    foreach ($shortcodes as $shortcode) {
        // do a COUNT query with a LIKE clause on some field
        // if count > 0, return true
    }

    return false;
}
```

Voters can utilize the `OHMedia\WysiwygBundle\Service\Wysiwyg` service to check
a variadic of strings as `$shortcodes`:

```php
<?php

namespace App\Security\Voter;

use App\Entity\Article;
use OHMedia\SecurityBundle\Entity\User;
use OHMedia\SecurityBundle\Security\Voter\AbstractEntityVoter;
use OHMedia\WysiwygBundle\Service\Wysiwyg;

class ArticleVoter extends AbstractEntityVoter
{
    // ...

    public const DELETE = 'delete';

    public function __construct(private Wysiwyg $wysiwyg)
    {
    }

    // ...

    protected function canDelete(Article $article, User $loggedIn): bool
    {
        // ...

        $shortcodes = [
            sprintf('article_preview(%d)', $article->getId()),
            sprintf('article_full(%d)', $article->getId()),
        ];

        return !$this->wysiwyg->shortcodesInUse(...$shortcodes);
    }
}
```

# TinyMCE Integration

## TinyMCE JS

Make sure webpack encore is setup to copy TinyMCE files:

```js
.copyFiles({
  from: './node_modules/tinymce',
  to: 'js/tinymce/[path][name].[ext]',
  pattern: /\.(js|min\.css)$/,
})
```

Such that `<script src="/backend/js/tinymce/tinymce.min.js"></script>` is valid.

There is a function to initialize a TinyMCE instance:

```js
OH_MEDIA_TINYMCE(container, selector);
```

This will happen automatically on page load for `textarea.tinymce`. You can also
add the following data attributes:
- `data-tinymce-allow-shortcodes` with a value of `false` will disable all shortcode plugins
- `data-tinymce-valid-elements` with a valid value for `valid_elements` in tinymce.init

You would then use these same values/overrides in the `wysiwyg` Twig function.

If you need more customization, initialized your own with `tinymce.init({...})`.
Just make sure if you are using a `WysiwygType::class` field in a form that you
override the default class.

## Shortcodes

Shortcodes can be made available to the TinyMCE editor simply by extending
`OHMedia\WysiwygBundle\Shortcodes\AbstractShortcodeProvider`.

See [EventShortcodeProvider](https://github.com/ohmediaorg/event-bundle/blob/main/src/Service/EventShortcodeProvider.php).

## Content Links

Content Links can be made available to the TinyMCE editor simply by extending
`OHMedia\WysiwygBundle\ContentLinks\AbstractContentLinkProvider`.

See [PageContentLinkProvider](https://github.com/ohmediaorg/page-bundle/blob/main/src/Service/PageContentLinkProvider.php).
