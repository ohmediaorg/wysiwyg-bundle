<?php

namespace OHMedia\WysiwygBundle\Twig;

use OHMedia\FileBundle\Service\FileBrowser;
use OHMedia\WysiwygBundle\Util\HtmlTags;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class TinymceExtension extends AbstractExtension
{
    private bool $rendered = false;
    private string $validElements;

    public function __construct(
        private FileBrowser $fileBrowser,
        #[Autowire('%oh_media_wysiwyg.tinymce.plugins%')]
        private string $plugins,
        #[Autowire('%oh_media_wysiwyg.tinymce.menu%')]
        private array $menu,
        #[Autowire('%oh_media_wysiwyg.tinymce.toolbar%')]
        private string $toolbar,
        #[Autowire('%oh_media_wysiwyg.tinymce.link_class_list%')]
        private array $linkClassList,
        #[Autowire('%oh_media_wysiwyg.tinymce.image_class_list%')]
        private array $imageClassList,
        #[Autowire('%oh_media_wysiwyg.allowed_tags%')]
        array $allowedTags,
    ) {
        if (!$this->fileBrowser->isEnabled()) {
            $this->plugins = str_replace([' ohfilebrowser', 'ohfilebrowser '], '', $this->plugins);
        }

        $this->validElements = HtmlTags::htmlTagsToTinymceElements(...$allowedTags);
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
            'link_class_list' => $this->linkClassList,
            'image_class_list' => $this->imageClassList,
            'file_browser_enabled' => $this->fileBrowser->isEnabled(),
            'valid_elements' => $this->validElements,
        ]);
    }
}
