<?php

namespace OHMedia\WysiwygBundle\Repository;

use Doctrine\ORM\QueryBuilder;

interface WysiwygRepositoryInterface
{
    public function getShortcodeQueryBuilder(string $shortcode): QueryBuilder;

    public function getShortcodeRoute(): string;

    public function getShortcodeRouteParams(mixed $entity): array;

    public function getShortcodeHeading(): string;

    public function getShortcodeLinkText(mixed $entity): string;
}
