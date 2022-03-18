<?php

namespace OHMedia\WysiwygBundle;

use OHMedia\WysiwygBundle\Twig\Extension\AbstractWysiwygExtension;
use Twig\Environment;
use Twig\Extension\SandboxExtension;
use Twig\Loader\LoaderInterface;
use Twig\Sandbox\SecurityPolicy;

class Wysiwyg
{
    private $environment;
    private $extentions;
    private $loader;

    public function __construct(LoaderInterface $loader)
    {
        $this->extentions = [];

        $this->loader = $loader;

        // TODO: pass in allowed_tags
    }

    public function addExtension(AbstractWysiwygExtension $extension)
    {
        $this->extensions[] = $extension;

        return $this;
    }

    public function getEnvironment(): Environment
    {
        if ($this->environment) {
            return $this->environment;
        }

        $this->environment = new Environment($this->loader, [
            'debug' => false,
            'charset' => 'UTF-8',
            'strict_variables' => false,
            'autoescape' => false,
            'cache' => false,
            'auto_reload' => null,
            'optimizations' => 0,
        ]);

        $functions = [];

        foreach ($this->extensions as $extension) {
            $this->environment->addExtension($extension);

            foreach ($extension->getFunctions() as $function) {
                $functions[] = $function->getName();
            }
        }

        $policy = new SecurityPolicy([], [], [], [], $functions);
        $sandbox = new SandboxExtension($policy);

        $this->environment->addExtension($sandbox);

        return $this->environment;
    }

    public function validate(string $wysiwyg)
    {
        // TODO
    }
}
