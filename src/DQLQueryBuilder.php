<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql;

use Yiisoft\Db\Expression\ArrayExpression;
use Yiisoft\Db\Expression\CaseExpression;
use Yiisoft\Db\Expression\JsonExpression;
use Yiisoft\Db\Expression\StructuredExpression;
use Yiisoft\Db\Pgsql\Builder\ArrayExpressionBuilder;
use Yiisoft\Db\Pgsql\Builder\ArrayOverlapsConditionBuilder;
use Yiisoft\Db\Pgsql\Builder\CaseExpressionBuilder;
use Yiisoft\Db\Pgsql\Builder\JsonOverlapsConditionBuilder;
use Yiisoft\Db\Pgsql\Builder\LikeConditionBuilder;
use Yiisoft\Db\Pgsql\Builder\StructuredExpressionBuilder;
use Yiisoft\Db\Pgsql\Builder\JsonExpressionBuilder;
use Yiisoft\Db\QueryBuilder\AbstractDQLQueryBuilder;
use Yiisoft\Db\QueryBuilder\Condition\ArrayOverlapsCondition;
use Yiisoft\Db\QueryBuilder\Condition\JsonOverlapsCondition;
use Yiisoft\Db\QueryBuilder\Condition\LikeCondition;

/**
 * Implements a DQL (Data Query Language) SQL statements for PostgreSQL Server.
 */
final class DQLQueryBuilder extends AbstractDQLQueryBuilder
{
    /**
     * Has an array of default condition classes.
     *
     * Extend this method if you want to change default condition classes for the query builder.
     *
     * {@see conditionClasses} docs for details.
     */
    protected function defaultConditionClasses(): array
    {
        return [
            ...parent::defaultConditionClasses(),
            'ILIKE' => LikeCondition::class,
            'NOT ILIKE' => LikeCondition::class,
            'OR ILIKE' => LikeCondition::class,
            'OR NOT ILIKE' => LikeCondition::class,
        ];
    }

    protected function defaultExpressionBuilders(): array
    {
        return [
            ...parent::defaultExpressionBuilders(),
            ArrayExpression::class => ArrayExpressionBuilder::class,
            ArrayOverlapsCondition::class => ArrayOverlapsConditionBuilder::class,
            JsonExpression::class => JsonExpressionBuilder::class,
            JsonOverlapsCondition::class => JsonOverlapsConditionBuilder::class,
            StructuredExpression::class => StructuredExpressionBuilder::class,
            LikeCondition::class => LikeConditionBuilder::class,
            CaseExpression::class => CaseExpressionBuilder::class,
        ];
    }
}
