<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Column;

use Yiisoft\Db\Pgsql\Constant\PgsqlColumnType;
use Yiisoft\Db\Schema\Column\ColumnInterface;

final class NumMultiRangeColumn extends AbstractMultiRangeColumn
{
    protected const DEFAULT_TYPE = PgsqlColumnType::NUMMULTIRANGE;

    protected function getRangeColumn(): ColumnInterface
    {
        return new NumRangeColumn();
    }
}
