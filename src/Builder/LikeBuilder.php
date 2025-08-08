<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Builder;

use Yiisoft\Db\QueryBuilder\Condition\Like;

/**
 * Build an object of {@see Like} into SQL expressions for PostgreSQL Server.
 */
final class LikeBuilder extends \Yiisoft\Db\QueryBuilder\Condition\Builder\LikeBuilder
{
    protected function parseOperator(Like $expression): array
    {
        [$not, $operator] = parent::parseOperator($expression);

        $operator = match ($expression->caseSensitive) {
            true => 'LIKE',
            false => 'ILIKE',
            default => $operator,
        };

        return [$not, $operator];
    }
}
