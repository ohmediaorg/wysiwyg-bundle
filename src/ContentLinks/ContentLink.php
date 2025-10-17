<?php

namespace OHMedia\WysiwygBundle\ContentLinks;

use OHMedia\WysiwygBundle\TinyMCE\TreeItem;

class ContentLink extends TreeItem
{
    private string $shortcode = '';

    public function setShortcode(string $shortcode): static
    {
        $this->shortcode = $shortcode;
        $this->children = [];

        return $this;
    }

    public function getId(): string
    {
        return json_encode([
            'href' => trim($this->shortcode, '{} '),
            'title' => $this->getText(),
            'text' => $this->getText(),
        ]);
    }
}
