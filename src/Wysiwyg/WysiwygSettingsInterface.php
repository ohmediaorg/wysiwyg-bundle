<?php

namespace OHMedia\WysiwygBundle\Wysiwyg;

abstract class AbstractWysiwygString
{
    public function __construct(
        private string $string,
        private ?array $allowedTags = null,
        private bool $allowShortcodes = true,
    ) {
    }

    // public function getString
}
