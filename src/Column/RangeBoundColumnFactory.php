<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Column;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Schema\Column\DateTimeColumn;
use Yiisoft\Db\Schema\Column\DoubleColumn;

/**
 * @internal
 */
final class RangeBoundColumnFactory
{
    public static function int4(): IntegerColumn
    {
        return new IntegerColumn();
    }

    public static function int8(): BigIntColumn|IntegerColumn
    {
        return PHP_INT_SIZE === 4 ? new BigIntColumn() : new IntegerColumn();
    }

    public static function num(): DoubleColumn
    {
        return new DoubleColumn();
    }

    public static function ts(): DateTimeColumn
    {
        return new DateTimeColumn(ColumnType::DATETIME);
    }

    public static function tsTz(): DateTimeColumn
    {
        return new DateTimeColumn(ColumnType::DATETIMETZ);
    }

    public static function date(): DateTimeColumn
    {
        return new DateTimeColumn(ColumnType::DATE);
    }
}
