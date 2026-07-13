<?php

namespace OHMedia\WysiwygBundle;

use OHMedia\WysiwygBundle\ContentLinks\AbstractContentLinkProvider;
use OHMedia\WysiwygBundle\DependencyInjection\Compiler\ContentLinkPass;
use OHMedia\WysiwygBundle\DependencyInjection\Compiler\ShortcodePass;
use OHMedia\WysiwygBundle\DependencyInjection\Compiler\WysiwygPass;
use OHMedia\WysiwygBundle\Repository\WysiwygRepositoryInterface;
use OHMedia\WysiwygBundle\Shortcodes\AbstractShortcodeProvider;
use OHMedia\WysiwygBundle\Twig\AbstractWysiwygExtension;
use OHMedia\WysiwygBundle\Util\HtmlTags;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class OHMediaWysiwygBundle extends AbstractBundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new ContentLinkPass());
        $container->addCompilerPass(new ShortcodePass());
        $container->addCompilerPass(new WysiwygPass());
    }

    public function configure(DefinitionConfigurator $definition): void
    {
        $this->configureTags($definition);
        $this->configureTinymce($definition);
    }

    private function configureTags(DefinitionConfigurator $definition): void
    {
        $allowedTags = $definition->rootNode()
            ->children()
                ->arrayNode('tags')
                    ->children();

        foreach (HtmlTags::SAFE as $tag) {
            $allowedTags->booleanNode($tag)
                ->defaultTrue()
            ->end();
        }

        foreach (HtmlTags::UNSAFE as $tag) {
            $allowedTags->booleanNode($tag)
                ->defaultFalse()
            ->end();
        }

        $allowedTags->end()->end()->end();
    }

    private function configureTinymce(DefinitionConfigurator $definition): void
    {
        $tinymce = $definition->rootNode()
            ->children()
                ->arrayNode('tinymce')
                    ->children();

        $this->configureTinymcePlugins($tinymce);

        $this->configureTinymceMenu($tinymce);

        $this->configureTinymceToolbar($tinymce);

        $tinymce->arrayNode('link_class_list')
            ->arrayPrototype()
                ->children()
                    ->scalarNode('title')
                        ->isRequired()
                        ->cannotBeEmpty()
                    ->end()
                    ->scalarNode('value')
                        ->isRequired()
                        ->cannotBeEmpty()
                    ->end()
                    ->booleanNode('button')
                        ->defaultTrue()
                    ->end()
                ->end()
            ->end()
        ->end();

        $tinymce->arrayNode('image_class_list')
            ->arrayPrototype()
                ->children()
                    ->scalarNode('title')
                        ->isRequired()
                        ->cannotBeEmpty()
                    ->end()
                    ->scalarNode('value')
                        ->isRequired()
                        ->cannotBeEmpty()
                    ->end()
                ->end()
            ->end()
        ->end();

        $tinymce->end()->end()->end();
    }

    private function configureTinymcePlugins(NodeBuilder $tinymce): void
    {
        $plugins = [
            'anchor',
            'autolink',
            'autoresize',
            'autosave',
            'charmap',
            'code',
            'directionality',
            'fullscreen',
            'image',
            'link',
            'lists',
            'advlist',
            'table',
            'ohshortcodes',
            'ohfilebrowser',
            'ohcontentlinks',
            'quickbars',
            'searchreplace',
            'visualblocks',
        ];

        $tinymce->arrayNode('plugins')
            ->acceptAndWrap(['string'])
            ->scalarPrototype()->end()
            ->defaultValue($plugins)
        ->end();
    }

    private function configureTinymceMenu(NodeBuilder $tinymce): void
    {
        $menus = [];

        $menus['file'] = [
            'title' => 'File',
            'items' => '',
        ];

        $menus['edit'] = [
            'title' => 'Edit',
            'items' => 'undo redo | cut copy paste pastetext | selectall | searchreplace',
        ];

        $menus['view'] = [
            'title' => 'View',
            'items' => 'code | visualblocks',
        ];

        $menus['insert'] = [
            'title' => 'Insert',
            'items' => 'link image | charmap hr | anchor',
        ];

        $menus['format'] = [
            'title' => 'Format',
            'items' => 'bold italic underline strikethrough superscript subscript codeformat | removeformat',
        ];

        $menus['tools'] = [
            'title' => 'Tools',
            'items' => '',
        ];

        $menus['table'] = [
            'title' => 'Table',
            'items' => 'inserttable | cell row column | advtablesort | tableprops deletetable',
        ];

        $menus['help'] = [
            'title' => 'Help',
            'items' => '',
        ];

        $menuConfig = $tinymce->arrayNode('menu')
            ->children();

        foreach ($menus as $key => $menu) {
            $menuConfig->arrayNode($key)
                ->children()
                    ->scalarNode('title')
                        ->defaultValue($menu['title'])
                    ->end()
                    ->scalarNode('items')
                        ->defaultValue($menu['items'])
                    ->end()
                ->end()
            ->end();
        }

        $menuConfig->end();

        $tinymce->end();
    }

    private function configureTinymceToolbar(NodeBuilder $tinymce): void
    {
        $toolbar = [
            'undo redo',
            'blocks image ohfilebrowser ohshortcodes ohcontentlinks',
            'bold italic underline numlist bullist',
            'alignleft aligncenter alignright alignjustify',
            'outdent indent',
            'fullscreen',
        ];

        $tinymce->scalarNode('toolbar')
            ->defaultValue(implode(' | ', $toolbar))
        ->end();
    }

    public function loadExtension(
        array $config,
        ContainerConfigurator $containerConfigurator,
        ContainerBuilder $containerBuilder,
    ): void {
        $containerConfigurator->import('../config/services.yaml');

        $allowedTags = [];

        foreach ($config['tags'] as $tag => $allowed) {
            if ($allowed) {
                $allowedTags[] = $tag;
            }
        }

        $containerConfigurator->parameters()->set('oh_media_wysiwyg.allowed_tags', $allowedTags);

        foreach ($config['tinymce']['link_class_list'] as $i => $linkClass) {
            if ($linkClass['button']) {
                $config['tinymce']['link_class_list'][$i]['value'] .= ' oh-tinymce-button';
            }
        }

        if ($config['tinymce']['link_class_list']) {
            array_unshift($config['tinymce']['link_class_list'], [
                'title' => 'None',
                'value' => '',
                'button' => false,
            ]);
        }

        if ($config['tinymce']['image_class_list']) {
            array_unshift($config['tinymce']['image_class_list'], [
                'title' => 'None',
                'value' => '',
            ]);
        }

        var_dump($config['tinymce']);

        $containerConfigurator->parameters()
            ->set('oh_media_wysiwyg.tinymce.plugins', implode(' ', $config['tinymce']['plugins']))
            ->set('oh_media_wysiwyg.tinymce.menu', $config['tinymce']['menu'])
            ->set('oh_media_wysiwyg.tinymce.toolbar', $config['tinymce']['toolbar'])
            ->set('oh_media_wysiwyg.tinymce.link_class_list', $config['tinymce']['link_class_list'])
            ->set('oh_media_wysiwyg.tinymce.image_class_list', $config['tinymce']['image_class_list'])
        ;

        $containerBuilder->registerForAutoconfiguration(AbstractContentLinkProvider::class)
            ->addTag('oh_media_wysiwyg.content_link_provider')
        ;

        $containerBuilder->registerForAutoconfiguration(AbstractWysiwygExtension::class)
            ->addTag('oh_media_wysiwyg.extension')
        ;

        $containerBuilder->registerForAutoconfiguration(WysiwygRepositoryInterface::class)
            ->addTag('oh_media_wysiwyg.repository')
        ;

        $containerBuilder->registerForAutoconfiguration(AbstractShortcodeProvider::class)
            ->addTag('oh_media_wysiwyg.shortcode_provider')
        ;
    }
}
