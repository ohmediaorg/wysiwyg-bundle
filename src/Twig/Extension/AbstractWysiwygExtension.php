<?php

namespace OHMedia\WysiwygBundle\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

abstract class AbstractWysiwygExtension extends AbstractExtension
{
    private $functions = [];

    final public function getFunctions(): array
    {
        return $this->functions;
    }

    final public function getTokenParsers(): array
    {
        return [];
    }

    final public function getNodeVisitors(): array
    {
        return [];
    }

    final public function getFilters(): array
    {
        return [];
    }

    final public function getTests(): array
    {
        return [];
    }

    final public function getOperators(): array
    {
        return [];
    }

    final protected function addFunction(
        string $name,
        $callable,
        bool $htmlSafe = false,
        bool $needsEnvironment = false
    )
    {
        $options = [
            'needs_environment' => $needsEnvironment
        ];

        if ($htmlSafe) {
            $options['is_safe'] = ['html'];
        }

        $this->functions[] = new TwigFunction($name, $callable, $options);

        return $this;
    }
}
