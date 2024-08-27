<?php

namespace OHMedia\WysiwygBundle\ContentLinks;

abstract class AbstractContentLinkProvider
{
    private array $contentLinks = [];

    abstract public function getTitle(): string;

    abstract public function buildContentLinks(): void;

    final public function getContentLinks(): array
    {
        return $this->contentLinks;
    }

    final protected function addContentLink(ContentLink $contentLink): static
    {
        $this->contentLinks[] = $contentLink;

        return $this;
    }
}
