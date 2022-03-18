Overview
========

This bundle offers functionality to store name/value wysiwyg in the DB.

Installation
------------

Enable the bundle in `config/bundles.php`:

```php
return [
    // ...
    OHMedia\WysiwygBundle\OHMediaWysiwygBundle::class => ['all' => true],
];
```

Make and run the migration:

```bash
$ php bin/console make:migration
$ php bin/console doctrine:migrations:migrate
```

How-To
------

Custom wysiwyg are created through the service:

```php
use OHMedia\WysiwygBundle\Service\Wysiwyg;

public function myAction(Wysiwyg $wysiwyg)
{
    $wysiwyg->set('app_my_new_wysiwyg', 'my value');
}
```

Once the wysiwyg is saved the value will be accessible in Twig:

```twig
{{ wysiwyg('app_my_new_wysiwyg') }}
```

or from the service itself:

```php
use OHMedia\WysiwygBundle\Service\Wysiwyg;

public function myAction(Wysiwyg $wysiwyg)
{
    $value = $wysiwyg->get('app_my_new_wysiwyg');
}
```

It is recommended to prefix your wysiwyg with your bundle name
to significantly reduce the chance of ID collision.

More Complex Data
-----------------

If your wysiwyg value is more complex than a string,
then you need to be able to convert it to and from a string.

First, create a service tagged with `oh_media_wysiwyg.transformer`:

```yaml
services:
    App\Wysiwyg\Transformer:
        tags: ["oh_media_wysiwyg.transformer"]
```

Your service should implement `OHMedia\WysiwygBundle\Interfaces\TransformerInterface`,
which requires three functions. One function that gives the ID of the wysiwyg,
and two functions to transform that wysiwyg's value.

```php
<?php

namespace App\Wysiwyg;

use App\Entity\User;
use App\Repository\UserRepository;
use OHMedia\WysiwygBundle\Interfaces\TransformerInterface;

class Transformer implements TransformerInterface
{
    private $userRepository;
    
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }
    
    public function getId(): string
    {
        return 'my_special_user';
    }
    
    public function transform($value): ?string
    {
        return (string) $value->getId();
    }
    
    public function reverseTransform(?string $value)
    {
        return $userRepository->find($value);
    }
}
```

The example transformer above will be the only transformer
to handle wysiwyg with ID 'my_special_user'.

You will need to create a transformer for every unique
wysiwyg ID you wish to transform.
