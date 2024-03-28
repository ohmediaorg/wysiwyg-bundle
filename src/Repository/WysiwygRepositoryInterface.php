<?php

namespace OHMedia\WysiwygBundle\Repository;

interface WysiwygRepositoryInterface
{
    public function containsWysiwygShortcodes(string ...$shortcodes): bool;
}
