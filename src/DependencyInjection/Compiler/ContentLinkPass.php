<?php

namespace OHMedia\WysiwygBundle\DependencyInjection\Compiler;

use OHMedia\WysiwygBundle\ContentLinks\ContentLinkManager;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ContentLinkPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        // always first check if the primary service is defined
        if (!$container->has(ContentLinkManager::class)) {
            return;
        }

        $definition = $container->findDefinition(ContentLinkManager::class);

        $tagged = $container->findTaggedServiceIds('oh_media_wysiwyg.content_link_provider');

        foreach ($tagged as $id => $tags) {
            $definition->addMethodCall('addContentLinkProvider', [new Reference($id)]);
        }
    }
}
