<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Expression\Builder;

use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Pgsql\Column\RangeBoundColumnFactory;
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
            Int4RangeValue::class => RangeBoundColumnFactory::int4(),
            Int8RangeValue::class => RangeBoundColumnFactory::int8(),
            NumRangeValue::class => RangeBoundColumnFactory::num(),
            TsRangeValue::class => RangeBoundColumnFactory::ts(),
            TsTzRangeValue::class => RangeBoundColumnFactory::tsTz(),
            DateRangeValue::class => RangeBoundColumnFactory::date(),
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
