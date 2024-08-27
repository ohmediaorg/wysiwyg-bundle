<?php

namespace OHMedia\WysiwygBundle\Twig;

use OHMedia\FileBundle\Service\FileBrowser;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class TinymceExtension extends AbstractExtension
{
    private bool $rendered = false;
    private string $allowedTags;

    public function __construct(
        private FileBrowser $fileBrowser,
        private string $plugins,
        private array $menu,
        private string $toolbar,
        array $allowedTags,
    ) {
        if (!$this->fileBrowser->isEnabled()) {
            $this->plugins = str_replace([' ohfilebrowser', 'ohfilebrowser '], '', $this->plugins);
            $this->toolbar = str_replace([' ohfilebrowser', 'ohfilebrowser '], '', $this->toolbar);
        }

        $this->allowedTags = implode(',', array_map(function ($tag) {
            return $tag.'[*]';
        }, $allowedTags));
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('tinymce_script', [$this, 'tinymceScript'], [
                'is_safe' => ['html'],
                'needs_environment' => true,
            ]),
        ];
    }

    public function tinymceScript(Environment $env)
    {
        if ($this->rendered) {
            return;
        }

        $this->rendered = true;

        return $env->render('@OHMediaWysiwyg/tinymce_script.html.twig', [
            'plugins' => $this->plugins,
            'menu' => $this->menu,
            'toolbar' => $this->toolbar,
            'file_browser_enabled' => $this->fileBrowser->isEnabled(),
            'allowed_tags' => $this->allowedTags,
        ]);
    }
}
