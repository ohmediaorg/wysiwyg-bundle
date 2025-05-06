<?php

namespace OHMedia\WysiwygBundle\Shortcodes;

class Shortcode
{
    public function __construct(
        public readonly string $label,
        public readonly string $shortcode,
    ) {
    }

    public function __toString(): string
    {
        return self::format($this->shortcode);
    }

    public static function format(string $shortcode): string
    {
        $shortcode = ltrim($shortcode, '{');
        $shortcode = rtrim($shortcode, '}');

        return '{{'.trim($shortcode).'}}';
    }
}
