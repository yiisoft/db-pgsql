<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Column;

use Yiisoft\Db\Schema\Column\ColumnInterface;

final class TsTzMultiRangeColumn extends AbstractMultiRangeColumn
{
    protected const DEFAULT_TYPE = PgsqlColumnType::TSTZMULTIRANGE;

    protected function getRangeColumn(): ColumnInterface
    {
        return new TsTzRangeColumn();
    }
}
