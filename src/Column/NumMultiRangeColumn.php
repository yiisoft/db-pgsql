<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Column;

use Yiisoft\Db\Schema\Column\ColumnInterface;

final class NumMultiRangeColumn extends AbstractMultiRangeColumn
{
    protected function getRangeColumn(): ColumnInterface
    {
        return new NumRangeColumn();
    }
}
