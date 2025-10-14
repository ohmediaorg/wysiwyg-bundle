<?php

namespace OHMedia\WysiwygBundle\Twig;

use OHMedia\WysiwygBundle\Shortcodes\Shortcode;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ShortcodeExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('shortcode', [$this, 'shortcode'], [
                'is_safe' => ['html'],
            ]),
            new TwigFunction('shortcode_script', [$this, 'shortcodeScript'], [
                'is_safe' => ['html'],
                'needs_environment' => true,
            ]),
        ];
    }

    public function shortcode(string $shortcode)
    {
        $shortcode = Shortcode::format($shortcode);

        return '<code data-shortcode="'.$shortcode.'" class="d-block">'.$shortcode.'</code>';
    }

    private bool $rendered = false;

    public function shortcodeScript(Environment $twig)
    {
        if ($this->rendered) {
            return;
        }

        $this->rendered = true;

        return $twig->render('@OHMediaWysiwyg/shortcode_script.html.twig');
    }
}
