<?php

namespace OHMedia\WysiwygBundle\Shortcodes;

class Shortcode
{
    public function __construct(
        public readonly string $label,
        public readonly string $shortcode,
        public readonly bool $dynamic = false,
    ) {
    }
}
