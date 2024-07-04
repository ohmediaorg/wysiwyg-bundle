<?php

namespace OHMedia\WysiwygBundle\Util;

class Shortcode
{
    public static function format(string $shortcode): string
    {
        $shortcode = ltrim($shortcode, '{');
        $shortcode = rtrim($shortcode, '}');

        return '{{'.trim($shortcode).'}}';
    }
}
