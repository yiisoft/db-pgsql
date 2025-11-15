<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Expression\Builder;

use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Pgsql\Expression\MultiRangeValue;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

/**
 * Builds expressions for {@see MultiRangeValue}.
 *
 * @implements ExpressionBuilderInterface<MultiRangeValue>
 */
final class MultiRangeValueBuilder implements ExpressionBuilderInterface
{
    public function __construct(
        private readonly QueryBuilderInterface $queryBuilder,
    ) {}

    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $ranges = array_map(
            fn(string|ExpressionInterface $range): string => trim(
                $range instanceof ExpressionInterface
                    ? $this->queryBuilder->prepareValue($range)
                    : $range,
                '\'',
            ),
            $expression->ranges,
        );
        return '\'{' . implode(',', $ranges) . '}\'';
    }
}
