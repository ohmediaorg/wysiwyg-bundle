<?php

namespace OHMedia\WysiwygBundle\DependencyInjection;

use OHMedia\WysiwygBundle\Twig\AbstractWysiwygExtension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class OHMediaWysiwygExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $allowedTags = [];

        foreach ($config['tags'] as $tag => $allowed) {
            if ($allowed) {
                $allowedTags[] = $tag;
            }
        }

        $container->setParameter('oh_media_wysiwyg.allowed_tags', $allowedTags);

        $container->registerForAutoconfiguration(AbstractWysiwygExtension::class)
            ->addTag('oh_media_wysiwyg.extension')
        ;
    }
}
