<?php

namespace OHMedia\WysiwygBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class WysiwygPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        // always first check if the primary service is defined
        if (!$container->has('oh_media_wysiwyg.environment')) {
            return;
        }

        $definition = $container->findDefinition('oh_media_wysiwyg.environment');

        $tagged = $container->findTaggedServiceIds('oh_media_wysiwyg.extension');

        foreach ($tagged as $id => $tags) {
            $definition->addMethodCall('addFunction', [new Reference($id)]);
        }
    }
}
