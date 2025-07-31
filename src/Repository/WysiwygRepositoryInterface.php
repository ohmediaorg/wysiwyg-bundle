<?php

namespace OHMedia\WysiwygBundle\Repository;

interface WysiwygRepositoryInterface
{
    public function getShortcodeQueryBuilder(string $shortcode): QueryBuilder;

    public function getEntityRoute(): string;
}
