<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Builder;

use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\QueryBuilder\Condition\Interface\LikeConditionInterface;

/**
 * Build an object of {@see LikeConditionInterface} into SQL expressions for PostgreSQL Server.
 */
final class LikeConditionBuilder extends \Yiisoft\Db\QueryBuilder\Condition\Builder\LikeConditionBuilder
{
    /**
     * @param LikeConditionInterface $expression
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    protected function parseOperator(ExpressionInterface $expression): array
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
