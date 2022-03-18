<?php

namespace OHMedia\WysiwygBundle;

class Wysiwyg
{
    private $environment;

    public function __construct(LoaderInterface $loader)
    {
        $this->loader = $loader;
    }

    public function getEnvironment()
    {
        if ($this->environment) {
            return $this->environment;
        }

        $this->environment = new TwigEnvironment($this->loader, [
            'cache' => false,
            'autoescape' => false,
            'auto_reload' => true,
        ]);

        return $this->environment;
    }
}
