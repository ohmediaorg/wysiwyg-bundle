<?php

namespace OHMedia\WysiwygBundle\TinyMCE;

abstract class TreeItem
{
    private array $children = [];

    abstract public function getId(): string;

    public function __construct(
        private string $title,
        private ?string $text = null,
    ) {
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getText(): ?string
    {
        return $this->text ?: $this->title;
    }

    public function getChildren(): array
    {
        return $this->children;
    }

    public function hasChildren(): bool
    {
        return count($this->children) > 0;
    }

    public function setChildren(TreeItem ...$treeItem): static
    {
        $this->children = $treeItem;

        return $this;
    }
}
