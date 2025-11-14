<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Expression\Builder;

use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Pgsql\Column\RangeBoundColumnFactory;
use Yiisoft\Db\Pgsql\Expression\NumRangeValue;
use Yiisoft\Db\Schema\Column\ColumnInterface;

/**
 * Builds expressions for {@see NumRangeValue}.
 *
 * @extends AbstractRangeValueBuilder<NumRangeValue>
 */
final class NumRangeValueBuilder extends AbstractRangeValueBuilder
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
        return RangeBoundColumnFactory::num();
    }
}
