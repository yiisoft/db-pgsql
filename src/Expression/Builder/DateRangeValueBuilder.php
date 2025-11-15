<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Expression\Builder;

use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Pgsql\Column\RangeBoundColumnFactory;
use Yiisoft\Db\Pgsql\Expression\DateRangeValue;
use Yiisoft\Db\Schema\Column\ColumnInterface;

/**
 * Builds expressions for {@see DateRangeValue}.
 *
 * @extends AbstractRangeValueBuilder<DateRangeValue>
 */
final class DateRangeValueBuilder extends AbstractRangeValueBuilder
{
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        return $this->buildRange(
            $expression->lower,
            $expression->upper,
            $expression->includeLower,
            $expression->includeUpper,
        );
    }

    protected function getBoundColumn(): ColumnInterface
    {
        return RangeBoundColumnFactory::date();
    }
}
