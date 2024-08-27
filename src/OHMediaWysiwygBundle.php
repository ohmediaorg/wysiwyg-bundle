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

        $definition->rootNode()
            ->children()
                ->arrayNode('tinymce')
                  ->children()
                    ->scalarNode('plugins')
                        ->defaultValue('autoresize code link lists ohshortcodes ohfilebrowser ohcontentlink')
                    ->end()
                    ->arrayNode('toolbar')
                        ->scalarPrototype()->end()
                        ->defaultValue([
                            'undo redo',
                            'blocks ohshortcodes ohfilebrowser ohcontentlink',
                            'bold italic numlist bullist',
                            'alignleft aligncenter alignright alignjustify',
                            'outdent indent',
                        ])
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
