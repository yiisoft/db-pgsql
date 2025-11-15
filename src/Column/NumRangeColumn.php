<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Column;

use Yiisoft\Db\Pgsql\Constant\PgsqlColumnType;
use Yiisoft\Db\Pgsql\Expression\NumRangeValue;
use Yiisoft\Db\Schema\Column\DoubleColumn;

final class NumRangeColumn extends AbstractRangeColumn
{
    protected const DEFAULT_TYPE = PgsqlColumnType::NUMRANGE;

    protected function getBoundColumn(): DoubleColumn
    {
        return RangeBoundColumnFactory::num();
    }

    protected function createRangeValue(?string $lower, ?string $upper, bool $includeLower, bool $includeUpper): NumRangeValue
    {
        $column = $this->getBoundColumn();
        return new NumRangeValue(
            $column->phpTypecast($lower),
            $column->phpTypecast($upper),
            $includeLower,
            $includeUpper,
        );
    }
}
