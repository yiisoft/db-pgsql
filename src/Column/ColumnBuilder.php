<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Column;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Schema\Column\ColumnInterface;
use Yiisoft\Db\Schema\Column\DateTimeColumn;
use Yiisoft\Db\Schema\Column\DoubleColumn;

final class ColumnBuilder extends \Yiisoft\Db\Schema\Column\ColumnBuilder
{
    public static function boolean(): BooleanColumn
    {
        return new BooleanColumn(ColumnType::BOOLEAN);
    }

    public static function bit(?int $size = null): BitColumn|BigBitColumn
    {
        $className = BitColumnInternal::className($size);

        /** @psalm-suppress UnsafeInstantiation */
        return new $className(ColumnType::BIT, size: $size);
    }

    public static function decimal(?int $size = 10, ?int $scale = 0): DoubleColumn
    {
        return new DoubleColumn(ColumnType::DECIMAL, scale: $scale, size: $size);
    }

    public static function tinyint(?int $size = null): IntegerColumn
    {
        return new IntegerColumn(ColumnType::TINYINT, size: $size);
    }

    public static function smallint(?int $size = null): IntegerColumn
    {
        return new IntegerColumn(ColumnType::SMALLINT, size: $size);
    }

    public static function integer(?int $size = null): IntegerColumn
    {
        return new IntegerColumn(ColumnType::INTEGER, size: $size);
    }

    public static function bigint(?int $size = null, bool $unsigned = false): BigIntColumn|IntegerColumn
    {
        return PHP_INT_SIZE === 4 || $unsigned
            ? new BigIntColumn(ColumnType::BIGINT, size: $size, unsigned: $unsigned)
            : new IntegerColumn(ColumnType::BIGINT, size: $size, unsigned: $unsigned);
    }

    public static function binary(?int $size = null): BinaryColumn
    {
        return new BinaryColumn(ColumnType::BINARY, size: $size);
    }

    public static function array(?ColumnInterface $column = null): ArrayColumn
    {
        return new ArrayColumn(ColumnType::ARRAY, column: $column);
    }

    public static function structured(?string $dbType = null, array $columns = []): StructuredColumn
    {
        return new StructuredColumn(ColumnType::STRUCTURED, dbType: $dbType, columns: $columns);
    }

    public static function date(): DateTimeColumn
    {
        return new DateTimeColumn(ColumnType::DATE);
    }

    public static function datetimeWithTimezone(?int $size = 0): DateTimeColumn
    {
        return new DateTimeColumn(ColumnType::DATETIMETZ, size: $size);
    }

    public static function datetime(?int $size = 0): DateTimeColumn
    {
        return new DateTimeColumn(ColumnType::DATETIME, size: $size);
    }

    public static function int4Range(): Int4RangeColumn
    {
        return new Int4RangeColumn();
    }

    public static function int8Range(): Int8RangeColumn
    {
        return new Int8RangeColumn();
    }

    public static function numRange(): NumRangeColumn
    {
        return new NumRangeColumn();
    }

    public static function tsRange(): TsRangeColumn
    {
        return new TsRangeColumn();
    }

    public static function tsTzRange(): TsTzRangeColumn
    {
        return new TsTzRangeColumn();
    }

    public static function dateRange(): DateRangeColumn
    {
        return new DateRangeColumn();
    }

    public static function int4MultiRange(): Int4MultiRangeColumn
    {
        return new Int4MultiRangeColumn();
    }

    public static function int8MultiRange(): Int8MultiRangeColumn
    {
        return new Int8MultiRangeColumn();
    }

    public static function numMultiRange(): NumMultiRangeColumn
    {
        return new NumMultiRangeColumn();
    }

    public static function tsMultiRange(): TsMultiRangeColumn
    {
        return new TsMultiRangeColumn();
    }

    public static function tsTzMultiRange(): TsTzMultiRangeColumn
    {
        return new TsTzMultiRangeColumn();
    }

    public static function dateMultiRange(): DateMultiRangeColumn
    {
        return new DateMultiRangeColumn();
    }
}
