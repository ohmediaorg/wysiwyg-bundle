<?php

namespace OHMedia\WysiwygBundle\Twig;

use Twig\Extension\AbstractExtension;

abstract class AbstractWysiwygExtension extends AbstractExtension
{
    final public function getTokenParsers(): array
    {
        return parent::getTokenParsers();
    }

    final public function getNodeVisitors(): array
    {
        return parent::getNodeVisitors();
    }

    final public function getFilters(): array
    {
        return parent::getFilters();
    }

    final public function getTests(): array
    {
        return parent::getTests();
    }

    final public function getOperators(): array
    {
        return parent::getOperators();
    }
}
