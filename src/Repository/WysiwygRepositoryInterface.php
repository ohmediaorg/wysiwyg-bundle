<?php

namespace OHMedia\WysiwygBundle\Repository;

use Doctrine\ORM\QueryBuilder;

interface WysiwygRepositoryInterface
{
    public function getShortcodeQueryBuilder(string $shortcode): QueryBuilder;

    public function getEntityRoute(): string;

    public function getEntityRouteParams(mixed $entity): array;

    public function getEntityName(): string;
}
