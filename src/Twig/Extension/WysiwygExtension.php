<?php

namespace OHMedia\WysiwygBundle\Twig\Extension;

use OHMedia\WysiwygBundle\Service\Wysiwyg;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class WysiwygExtension extends AbstractExtension
{
    private $wysiwyg;

    public function __construct(Wysiwyg $wysiwyg)
    {
        $this->wysiwyg = $wysiwyg;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('wysiwyg', [$this, 'wysiwyg'], [
                'is_safe' => ['html'],
            ]),
        ];
    }

    public function wysiwyg(string $wysiwyg, ?array $allowedTags = null)
    {
        return $this->wysiwyg->render($wysiwyg, $allowedTags);
    }
}
