<?php

namespace OHMedia\WysiwygBundle\ContentLinks;

use OHMedia\WysiwygBundle\TinyMCE\TreeItemBuilder;

class ContentLinkManager
{
    private array $contentLinkProviders = [];

    public function addContentLinkProvider(AbstractContentLinkProvider $contentLinkProvider): self
    {
        $this->contentLinkProviders[] = $contentLinkProvider;

        return $this;
    }

    public function getContentLinks()
    {
        usort($this->contentLinkProviders, function (
            AbstractContentLinkProvider $a,
            AbstractContentLinkProvider $b
        ) {
            return $a->getTitle() <=> $b->getTitle();
        });

        $tabs = [];

        $treeItemBuilder = new TreeItemBuilder();

        foreach ($this->contentLinkProviders as $i => $contentLinkProvider) {
            $contentLinkProvider->buildContentLinks();

            $contentLinks = $contentLinkProvider->getContentLinks();

            if (!$contentLinks) {
                continue;
            }

            $items = $treeItemBuilder->getTreeItems(...$contentLinks);

            if (!$items) {
                continue;
            }

            $tabs[] = [
                'title' => $contentLinkProvider->getTitle(),
                'items' => [[
                    'type' => 'tree',
                    'items' => $items,
                ]],
            ];
        }

        return $tabs;
    }
}
