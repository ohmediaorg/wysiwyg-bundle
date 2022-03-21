<?php

namespace OHMedia\WysiwygBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('oh_media_wysiwyg');

        $default = [
            'a', 'abbr', 'address', 'article', 'aside',
            'b', 'blockquote', 'br', 'button',
            'caption', 'cite', 'code', 'col', 'colgroup',
            'dd', 'dfn', 'div', 'dl', 'dt',
            'em', 'embed',
            'font', 'figcaption', 'figure',
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'hr',
            'i', 'iframe', 'img',
            'kbd',
            'li',
            'ol',
            'p', 'picture', 'pre',
            'q',
            'section', 'small', 'span', 'strong', 'sub', 'sup', 'svg',
            'table', 'tbody', 'td', 'tfoot', 'th', 'thead', 'time', 'tr',
            'u', 'ul',
        ];

        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('allowed_tags')
                    ->scalarPrototype()
                        ->defaultValue($default)
                        ->isRequired()
                        ->cannotBeEmpty()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
