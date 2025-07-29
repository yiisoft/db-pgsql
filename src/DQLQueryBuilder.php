<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql;

use Yiisoft\Db\Expression\ArrayExpression;
use Yiisoft\Db\Expression\CaseExpression;
use Yiisoft\Db\Expression\JsonExpression;
use Yiisoft\Db\Expression\StructuredExpression;
use Yiisoft\Db\Pgsql\Builder\ArrayExpressionBuilder;
use Yiisoft\Db\Pgsql\Builder\ArrayOverlapsBuilder;
use Yiisoft\Db\Pgsql\Builder\CaseExpressionBuilder;
use Yiisoft\Db\Pgsql\Builder\JsonOverlapsBuilder;
use Yiisoft\Db\Pgsql\Builder\LikeBuilder;
use Yiisoft\Db\Pgsql\Builder\StructuredExpressionBuilder;
use Yiisoft\Db\Pgsql\Builder\JsonExpressionBuilder;
use Yiisoft\Db\QueryBuilder\AbstractDQLQueryBuilder;
use Yiisoft\Db\QueryBuilder\Condition\Like;
use Yiisoft\Db\QueryBuilder\Condition\ArrayOverlaps;
use Yiisoft\Db\QueryBuilder\Condition\JsonOverlaps;

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
            'ILIKE' => Like::class,
            'NOT ILIKE' => Like::class,
            'OR ILIKE' => Like::class,
            'OR NOT ILIKE' => Like::class,
        ];
    }

    protected function defaultExpressionBuilders(): array
    {
        return [
            ...parent::defaultExpressionBuilders(),
            ArrayExpression::class => ArrayExpressionBuilder::class,
            ArrayOverlaps::class => ArrayOverlapsBuilder::class,
            JsonExpression::class => JsonExpressionBuilder::class,
            JsonOverlaps::class => JsonOverlapsBuilder::class,
            StructuredExpression::class => StructuredExpressionBuilder::class,
            Like::class => LikeBuilder::class,
            CaseExpression::class => CaseExpressionBuilder::class,
        ];
    }
}
