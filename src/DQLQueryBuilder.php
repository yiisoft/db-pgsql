<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql;

use Yiisoft\Db\Expression\Value\ArrayValue;
use Yiisoft\Db\Expression\Statement\CaseX;
use Yiisoft\Db\Expression\Function\ArrayMerge;
use Yiisoft\Db\Expression\Value\JsonValue;
use Yiisoft\Db\Expression\Value\StructuredValue;
use Yiisoft\Db\Pgsql\Builder\ArrayValueBuilder;
use Yiisoft\Db\Pgsql\Builder\ArrayMergeBuilder;
use Yiisoft\Db\Pgsql\Builder\ArrayOverlapsBuilder;
use Yiisoft\Db\Pgsql\Builder\CaseXBuilder;
use Yiisoft\Db\Pgsql\Builder\JsonOverlapsBuilder;
use Yiisoft\Db\Pgsql\Builder\LikeBuilder;
use Yiisoft\Db\Pgsql\Builder\StructuredValueBuilder;
use Yiisoft\Db\Pgsql\Builder\JsonValueBuilder;
use Yiisoft\Db\Pgsql\Expression\Builder\DateRangeValueBuilder;
use Yiisoft\Db\Pgsql\Expression\Builder\Int4RangeValueBuilder;
use Yiisoft\Db\Pgsql\Expression\Builder\Int8RangeValueBuilder;
use Yiisoft\Db\Pgsql\Expression\Builder\MultiRangeValueBuilder;
use Yiisoft\Db\Pgsql\Expression\Builder\NumRangeValueBuilder;
use Yiisoft\Db\Pgsql\Expression\Builder\TsRangeValueBuilder;
use Yiisoft\Db\Pgsql\Expression\Builder\TsTzRangeValueBuilder;
use Yiisoft\Db\Pgsql\Expression\DateRangeValue;
use Yiisoft\Db\Pgsql\Expression\Int4RangeValue;
use Yiisoft\Db\Pgsql\Expression\Int8RangeValue;
use Yiisoft\Db\Pgsql\Expression\MultiRangeValue;
use Yiisoft\Db\Pgsql\Expression\NumRangeValue;
use Yiisoft\Db\Pgsql\Expression\TsRangeValue;
use Yiisoft\Db\Pgsql\Expression\TsTzRangeValue;
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
            ArrayValue::class => ArrayValueBuilder::class,
            ArrayOverlaps::class => ArrayOverlapsBuilder::class,
            JsonValue::class => JsonValueBuilder::class,
            JsonOverlaps::class => JsonOverlapsBuilder::class,
            StructuredValue::class => StructuredValueBuilder::class,
            Like::class => LikeBuilder::class,
            NotLike::class => LikeBuilder::class,
            CaseX::class => CaseXBuilder::class,
            ArrayMerge::class => ArrayMergeBuilder::class,
            DateRangeValue::class => DateRangeValueBuilder::class,
            Int4RangeValue::class => Int4RangeValueBuilder::class,
            Int8RangeValue::class => Int8RangeValueBuilder::class,
            NumRangeValue::class => NumRangeValueBuilder::class,
            TsRangeValue::class => TsRangeValueBuilder::class,
            TsTzRangeValue::class => TsTzRangeValueBuilder::class,
            MultiRangeValue::class => MultiRangeValueBuilder::class,
        ];
    }
}
