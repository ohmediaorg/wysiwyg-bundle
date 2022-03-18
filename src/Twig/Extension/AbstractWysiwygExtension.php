<?php

namespace OHMedia\WysiwygBundle\Twig\Extension;

use Twig\Extension\AbstractExtension;

abstract class AbstractWysiwygExtension implements AbstractExtension
{
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
}
