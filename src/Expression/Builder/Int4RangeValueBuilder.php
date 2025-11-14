<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Expression\Builder;

use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Pgsql\Column\RangeBoundColumnFactory;
use Yiisoft\Db\Pgsql\Expression\Int4RangeValue;
use Yiisoft\Db\Schema\Column\ColumnInterface;

/**
 * Builds expressions for {@see Int4RangeValue}.
 *
 * @extends AbstractRangeValueBuilder<Int4RangeValue>
 */
final class Int4RangeValueBuilder extends AbstractRangeValueBuilder
{
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        return $this->buildRange(
            $expression->lower,
            $expression->upper,
            $expression->includeLower,
            $expression->includeUpper,
            $params,
        );
    }

    protected function getBoundColumn(): ColumnInterface
    {
        return RangeBoundColumnFactory::int4();
    }
}
