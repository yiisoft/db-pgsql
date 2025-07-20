<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Builder;

use Yiisoft\Db\QueryBuilder\Condition\LikeCondition;

/**
 * Build an object of {@see LikeCondition} into SQL expressions for PostgreSQL Server.
 */
final class LikeConditionBuilder extends \Yiisoft\Db\QueryBuilder\Condition\Builder\LikeConditionBuilder
{
    protected function parseOperator(LikeCondition $expression): array
    {
        [$andor, $not, $operator] = parent::parseOperator($expression);

        $operator = match ($expression->getCaseSensitive()) {
            true => 'LIKE',
            false => 'ILIKE',
            default => $operator,
        };

        return [$andor, $not, $operator];
    }
}
