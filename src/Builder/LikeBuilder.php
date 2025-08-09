<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Builder;

use Yiisoft\Db\QueryBuilder\Condition\AbstractLike;
use Yiisoft\Db\QueryBuilder\Condition\Like;
use Yiisoft\Db\QueryBuilder\Condition\NotLike;

/**
 * Build an object of {@see Like} into SQL expressions for PostgreSQL Server.
 */
final class LikeBuilder extends \Yiisoft\Db\QueryBuilder\Condition\Builder\LikeBuilder
{
    protected function getOperatorData(AbstractLike $condition): array
    {
        return match ($condition::class) {
            Like::class => [false, $condition->caseSensitive === false ? 'ILIKE' : 'LIKE'],
            NotLike::class => [true, $condition->caseSensitive === false ? 'NOT ILIKE' : 'NOT LIKE'],
            default => $this->throwUnsupportedConditionException($condition),
        };
    }
}
