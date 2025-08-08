<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Builder;

use Yiisoft\Db\QueryBuilder\Condition\Like;

/**
 * Build an object of {@see Like} into SQL expressions for PostgreSQL Server.
 */
final class LikeBuilder extends \Yiisoft\Db\QueryBuilder\Condition\Builder\LikeBuilder
{
    protected function parseOperator(Like $condition): array
    {
        [$not, $operator] = parent::parseOperator($condition);

        $operator = match ($condition->caseSensitive) {
            true => 'LIKE',
            false => 'ILIKE',
            default => $operator,
        };

        return [$not, $operator];
    }
}
