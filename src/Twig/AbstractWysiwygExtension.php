<?php

namespace OHMedia\WysiwygBundle\Twig;

use Twig\Extension\AbstractExtension;

abstract class AbstractWysiwygExtension extends AbstractExtension
{
    final public function getTokenParsers()
    {
        return parent::getTokenParsers();
    }

    final public function getNodeVisitors()
    {
        return parent::getNodeVisitors();
    }

    final public function getFilters()
    {
        return parent::getFilters();
    }

    final public function getTests()
    {
        return parent::getTests();
    }

    final public function getOperators()
    {
        return parent::getOperators();
    }
}
