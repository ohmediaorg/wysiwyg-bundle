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

        $allowedTags->end()->end();
    }

    private function configureTinymce(DefinitionConfigurator $definition): void
    {
        $plugins = [
            'autoresize',
            'code',
            'link',
            'lists',
            'ohshortcodes',
            'ohfilebrowser',
            'ohcontentlinks',
        ];

        $menu = [];

        $menu['file'] = [
            'title' => 'File',
            'items' => 'newdocument restoredraft | preview | importword exportpdf exportword | print | deleteallconversations',
        ];

        $menu['edit'] = [
            'title' => 'Edit',
            'items' => 'undo redo | cut copy paste pastetext | selectall | searchreplace',
        ];

        $menu['view'] = [
            'title' => 'View',
            'items' => 'code revisionhistory | visualaid visualchars visualblocks | spellchecker | preview fullscreen | showcomments',
        ];

        $menu['insert'] = [
            'title' => 'Insert',
            'items' => 'image link media addcomment pageembed codesample inserttable | math | charmap emoticons hr | pagebreak nonbreaking anchor tableofcontents | insertdatetime',
        ];

        $menu['format'] = [
            'title' => 'Format',
            'items' => 'bold italic underline strikethrough superscript subscript codeformat | styles blocks fontfamily fontsize align lineheight | forecolor backcolor | language | removeformat',
        ];

        $menu['tools'] = [
            'title' => 'Tools',
            'items' => 'spellchecker spellcheckerlanguage | a11ycheck code wordcount',
        ];

        $menu['table'] = [
            'title' => 'Table',
            'items' => 'inserttable | cell row column | advtablesort | tableprops deletetable',
        ];

        $menu['help'] = [
            'title' => 'Help',
            'items' => 'help',
        ];

        $toolbar = [
            'undo redo',
            'blocks ohshortcodes ohfilebrowser ohcontentlink',
            'bold italic numlist bullist',
            'alignleft aligncenter alignright alignjustify',
            'outdent indent',
        ];

        $definition->rootNode()
            ->children()
                ->arrayNode('tinymce')
                  ->children()
                    ->scalarNode('plugins')
                        ->defaultValue(implode(' ', $plugins))
                    ->end()
                    ->arrayNode('menu')
                        ->useAttributeAsKey('name')
                        ->defaultValue($menu)
                        ->arrayPrototype()
                            ->children()
                                ->scalarNode('title')->end()
                                ->scalarNode('items')->end()
                            ->end()
                        ->end()
                    ->end()
                    ->scalarNode('toolbar')
                        ->defaultValue(implode(' | ', $toolbar))
                    ->end()
                ->end()
            ->end()
        ;
    }

    public function loadExtension(
        array $config,
        ContainerConfigurator $containerConfigurator,
        ContainerBuilder $containerBuilder
    ): void {
        $containerConfigurator->import('../config/services.yaml');

        $allowedTags = [];

        foreach ($config['tags'] as $tag => $allowed) {
            if ($allowed) {
                $allowedTags[] = $tag;
            }
        }

        $containerConfigurator->parameters()->set('oh_media_wysiwyg.allowed_tags', $allowedTags);

        $containerConfigurator->parameters()
            ->set('oh_media_wysiwyg.tinymce.plugins', $config['tinymce']['plugins'])
            ->set('oh_media_wysiwyg.tinymce.menu', $config['tinymce']['menu'])
            ->set('oh_media_wysiwyg.tinymce.toolbar', $config['tinymce']['toolbar'])
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
