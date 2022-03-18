<?php

namespace OHMedia\WysiwygBundle;

use OHMedia\WysiwygBundle\Twig\Extension\AbstractWysiwygExtension;
use Twig\Environment;
use Twig\Error\Error;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\SandboxExtension;
use Twig\Loader\LoaderInterface;
use Twig\Sandbox\SecurityPolicy;
use Twig\Sandbox\SecurityError;

class Wysiwyg
{
    private $environment;
    private $extensions;
    private $loader;

    public function __construct(LoaderInterface $loader)
    {
        $this->extensions = [];

        $this->loader = $loader;
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

    public function render(string $wysiwyg)
    {
        $environment = $this->getEnvironment();

        $template = $environment->createTemplate($wysiwyg);

        return $environment->render($template);
    }

    public function validate(string $wysiwyg): string
    {
        try {
            // attempt to render
            $this->render($wysiwyg);

            return '';
        }
        catch (SecurityError $error) {
            return 'Security error';
        }
        catch (LoaderError $error) {
            // should not get here because there are no templates to load
            return 'Loader error';
        }
        catch (RuntimeError $error) {
            return 'Runtime error';
        }
        catch (SyntaxError $error) {
            return 'Syntax error.';
        }
        catch (Error $error) {
            return 'Unforeseen error.';
        }
    }
}
