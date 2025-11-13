<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Expression\Builder;

use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Pgsql\Column\ColumnBuilder;
use Yiisoft\Db\Pgsql\Expression\DateRangeValue;
use Yiisoft\Db\Pgsql\Expression\Int4RangeValue;
use Yiisoft\Db\Pgsql\Expression\Int8RangeValue;
use Yiisoft\Db\Pgsql\Expression\NumRangeValue;
use Yiisoft\Db\Pgsql\Expression\TsRangeValue;
use Yiisoft\Db\Pgsql\Expression\TsTzRangeValue;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

/**
 * Builds expressions for {@see RangeValue}.
 *
 * @implements ExpressionBuilderInterface<DateRangeValue|Int4RangeValue|Int8RangeValue|NumRangeValue|TsRangeValue|TsTzRangeValue>
 */
final class RangeValueBuilder implements ExpressionBuilderInterface
{
    public function __construct(
        private readonly QueryBuilderInterface $queryBuilder,
    ) {}

    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $column = match ($expression::class) {
            Int4RangeValue::class => ColumnBuilder::integer(),
            Int8RangeValue::class => ColumnBuilder::bigint(),
            NumRangeValue::class => ColumnBuilder::decimal(),
            TsRangeValue::class => ColumnBuilder::datetime(),
            TsTzRangeValue::class => ColumnBuilder::datetimeWithTimezone(),
            DateRangeValue::class => ColumnBuilder::date(),
        };

        return ($expression->includeLower ? '[' : '(')
            . $this->queryBuilder->buildValue(
                $column->dbTypecast($expression->lower),
                $params,
            )
            . ', '
            . $this->queryBuilder->buildValue(
                $column->dbTypecast($expression->upper),
                $params,
            )
            . ($expression->includeUpper ? ']' : ')');
    }
}
