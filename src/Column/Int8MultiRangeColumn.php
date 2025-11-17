<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Column;

use Yiisoft\Db\Schema\Column\ColumnInterface;

final class Int8MultiRangeColumn extends AbstractMultiRangeColumn
{
    protected const DEFAULT_TYPE = PgsqlColumnType::INT8MULTIRANGE;

    protected function getRangeColumn(): ColumnInterface
    {
        return new Int8RangeColumn();
    }
}
