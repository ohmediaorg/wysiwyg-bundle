<?php

namespace OHMedia\WysiwygBundle\DependencyInjection;

use OHMedia\WysiwygBundle\Twig\Extension\AbstractWysiwygExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class OHMediaWysiwygExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        foreach ($config as $key => $value) {
            $container->setParameter("oh_media_wysiwyg.$key", $value);
        }

        $container->registerForAutoconfiguration(AbstractWysiwygExtension::class)
            ->addTag('oh_media_wysiwyg.extension')
        ;
    }
}
