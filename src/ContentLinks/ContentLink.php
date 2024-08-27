<?php

namespace OHMedia\WysiwygBundle\ContentLinks;

class ContentLink
{
    private string $shortcode = '';
    private array $children = [];

    public function __construct(private string $leafTitle, private ?string $linkText = null)
    {
    }

    public function getLeafTitle(): string
    {
        return $this->leafTitle;
    }

    public function getLinkText(): ?string
    {
        return $this->linkText ?: $this->leafTitle;
    }

    public function getShortcode(): string
    {
        return $this->shortcode;
    }

    public function setShortcode(string $shortcode): static
    {
        $this->shortcode = $shortcode;
        $this->children = [];

        return $this;
    }

    public function getChildren(): array
    {
        return $this->children;
    }

    public function hasChildren(): bool
    {
        return count($this->children) > 0;
    }

    public function setChildren(ContentLink ...$children): static
    {
        $this->children = $children;
        $this->shortcode = '';

        return $this;
    }
}
