<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Column;

use Yiisoft\Db\Pgsql\Constant\PgsqlColumnType;
use Yiisoft\Db\Pgsql\Expression\TsRangeValue;
use Yiisoft\Db\Schema\Column\ColumnInterface;

/**
 * @extends AbstractMultiRangeColumn<TsRangeValue>
 */
final class TsMultiRangeColumn extends AbstractMultiRangeColumn
{
    protected const DEFAULT_TYPE = PgsqlColumnType::TSMULTIRANGE;

    protected function getRangeColumn(): ColumnInterface
    {
        return new TsRangeColumn();
    }
}
