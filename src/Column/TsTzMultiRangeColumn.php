<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Column;

use Yiisoft\Db\Pgsql\Constant\PgsqlColumnType;
use Yiisoft\Db\Pgsql\Expression\TsTzRangeValue;
use Yiisoft\Db\Schema\Column\ColumnInterface;

/**
 * @extends AbstractMultiRangeColumn<TsTzRangeValue>
 */
final class TsTzMultiRangeColumn extends AbstractMultiRangeColumn
{
    protected const DEFAULT_TYPE = PgsqlColumnType::TSTZMULTIRANGE;

    protected function getRangeColumn(): ColumnInterface
    {
        return new TsTzRangeColumn();
    }
}
