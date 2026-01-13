<?php

namespace OHMedia\WysiwygBundle\DependencyInjection\Compiler;

use OHMedia\WysiwygBundle\Service\Wysiwyg;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class WysiwygPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        // always first check if the primary service is defined
        if (!$container->has(Wysiwyg::class)) {
            return;
        }

        $definition = $container->findDefinition(Wysiwyg::class);

        $tagged = $container->findTaggedServiceIds('oh_media_wysiwyg.extension');

        foreach ($tagged as $id => $tags) {
            $definition->addMethodCall('addExtension', [new Reference($id)]);
        }

        $tagged = $container->findTaggedServiceIds('oh_media_wysiwyg.repository');

        foreach ($tagged as $id => $tags) {
            $definition->addMethodCall('addRepository', [new Reference($id)]);
        }
    }
}
