# Overview

This bundle offers a form field that allows certain HTML tags and Twig syntax,
while filtering out anything else.

## Installation

Enable the bundle in `config/bundles.php`:

```php
return [
    // ...
    OHMedia\WysiwygBundle\OHMediaWysiwygBundle::class => ['all' => true],
];
```

Create the minimum config file in `config/oh_media_wysiwyg.yaml`:

```yaml
oh_media_wysiwyg:
    tags:
```

This will enable/disable HTML tags based on constant values inside of
`OHMedia\WysiwygBundle\Util\HtmlTags`.

You can also specify your own preferences:

```yaml
oh_media_wysiwyg:
    tags:
        fieldset: true
        table: false
```

## Form Field

Add the field to your form:

```php
use OHMedia\WysiwygBundle\Form\Type\WysiwygType;

$builder->add('description', WysiwygType::class);
```

You can also specified allowed tags per form field:

```php
use OHMedia\WysiwygBundle\Form\Type\WysiwygType;

$builder->add('description', WysiwygType::class, [
    'allowed_tags' => ['p', 'div', 'span'],
]);
```

You will need to apply your preferred WYSIWYG editor to the field manually.

## Twig Functions

You can define simple twig functions for use in the Wysiwyg field content. These
functions can have a single integer parameter at most. No variables, filters, etc.
This keeps the syntax simple for the average user.

Create an extension as usual, but extend
`OHMedia\WysiwygBundle\Twig\Extension\AbstractWysiwygExtension`:

```php
namespace App\Twig;

use OHMedia\WysiwygBundle\Twig\Extension\AbstractWysiwygExtension;
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
{{ wysiwyg(myEntity.description) }}
```

If you overwrote the `allowed_tags` in the form field, you will need to pass
that same array as the second parameter of the render function.

You can do this in PHP:

```php
$rendered = $wysiwyg->render($description, ['p', 'div', 'span']);
```

or in Twig:

```twig
{{ wysiwyg(myEntity.description, ['p', 'div', 'span']) }}
```