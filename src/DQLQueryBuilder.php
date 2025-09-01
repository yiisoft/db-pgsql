<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql;

use Yiisoft\Db\Expression\Value\ArrayExpression;
use Yiisoft\Db\Expression\Statement\CaseX;
use Yiisoft\Db\Expression\Function\ArrayMerge;
use Yiisoft\Db\Expression\Value\JsonExpression;
use Yiisoft\Db\Expression\Value\StructuredExpression;
use Yiisoft\Db\Pgsql\Builder\ArrayExpressionBuilder;
use Yiisoft\Db\Pgsql\Builder\ArrayMergeBuilder;
use Yiisoft\Db\Pgsql\Builder\ArrayOverlapsBuilder;
use Yiisoft\Db\Pgsql\Builder\CaseXBuilder;
use Yiisoft\Db\Pgsql\Builder\JsonOverlapsBuilder;
use Yiisoft\Db\Pgsql\Builder\LikeBuilder;
use Yiisoft\Db\Pgsql\Builder\StructuredExpressionBuilder;
use Yiisoft\Db\Pgsql\Builder\JsonExpressionBuilder;
use Yiisoft\Db\QueryBuilder\AbstractDQLQueryBuilder;
use Yiisoft\Db\QueryBuilder\Condition\Like;
use Yiisoft\Db\QueryBuilder\Condition\ArrayOverlaps;
use Yiisoft\Db\QueryBuilder\Condition\JsonOverlaps;
use Yiisoft\Db\QueryBuilder\Condition\NotLike;

/**
 * Implements a DQL (Data Query Language) SQL statements for PostgreSQL Server.
 */
final class DQLQueryBuilder extends AbstractDQLQueryBuilder
{
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
            NotLike::class => LikeBuilder::class,
            CaseX::class => CaseXBuilder::class,
            ArrayMerge::class => ArrayMergeBuilder::class,
        ];
    }
}
