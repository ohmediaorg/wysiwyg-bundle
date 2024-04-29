<?php

namespace OHMedia\WysiwygBundle\Twig;

use OHMedia\WysiwygBundle\Service\Wysiwyg;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class WysiwygExtension extends AbstractExtension
{
    public function __construct(private Wysiwyg $wysiwyg)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('wysiwyg', [$this, 'wysiwyg'], [
                'is_safe' => ['html'],
            ]),
        ];
    }

    public function wysiwyg(?string $wysiwyg, array $allowedTags = null)
    {
        return $this->wysiwyg->render((string) $wysiwyg, $allowedTags);
    }
}
