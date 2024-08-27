<?php

namespace OHMedia\WysiwygBundle\Shortcodes;

abstract class AbstractShortcodeProvider
{
    private array $shortcodes = [];
    private bool $built = false;

    abstract public function getTitle(): string;

    abstract public function buildShortcodes(): void;

    final public function getShortcodes(): array
    {
        if (!$this->built) {
            $this->built = true;

            $this->buildShortcodes();
        }

        return $this->shortcodes;
    }

    final protected function addShortcode(Shortcode $shortcode): static
    {
        $this->shortcodes[] = $shortcode;

        return $this;
    }
}
