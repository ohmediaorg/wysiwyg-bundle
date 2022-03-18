<?php

namespace OHMedia\WysiwygBundle\Twig;

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
            new TwigFunction('wysiwyg', [$this, 'getWysiwyg'])
        ];
    }

    public function getWysiwyg(string $wysiwyg): mixed
    {
        return $this->wysiwyg->get($wysiwyg);
    }
}
