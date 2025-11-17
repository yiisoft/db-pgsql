<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Column;

use Yiisoft\Db\Pgsql\Constant\PgsqlColumnType;
use Yiisoft\Db\Schema\Column\ColumnInterface;

final class Int4MultiRangeColumn extends AbstractMultiRangeColumn
{
    protected const DEFAULT_TYPE = PgsqlColumnType::INT4MULTIRANGE;

    protected function getRangeColumn(): ColumnInterface
    {
        return new Int4RangeColumn();
    }
}
