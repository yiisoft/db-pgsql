<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Column;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Schema\Column\ColumnInterface;

final class ColumnBuilder extends \Yiisoft\Db\Schema\Column\ColumnBuilder
{
    public static function boolean(): BooleanColumn
    {
        return new BooleanColumn(ColumnType::BOOLEAN);
    }

    public static function bit(int|null $size = null): BitColumn
    {
        return new BitColumn(ColumnType::BIT, size: $size);
    }

    public static function tinyint(int|null $size = null): IntegerColumn
    {
        return new IntegerColumn(ColumnType::TINYINT, size: $size);
    }

    public static function smallint(int|null $size = null): IntegerColumn
    {
        return new IntegerColumn(ColumnType::SMALLINT, size: $size);
    }

    public static function integer(int|null $size = null): IntegerColumn
    {
        return new IntegerColumn(ColumnType::INTEGER, size: $size);
    }

    public static function bigint(int|null $size = null): IntegerColumn
    {
        return new IntegerColumn(ColumnType::BIGINT, size: $size);
    }

    public static function binary(int|null $size = null): BinaryColumn
    {
        return new BinaryColumn(ColumnType::BINARY, size: $size);
    }

    public static function array(ColumnInterface|null $column = null): ArrayColumn
    {
        return new ArrayColumn(ColumnType::ARRAY, column: $column);
    }

    public static function structured(string|null $dbType = null, array $columns = []): StructuredColumn
    {
        return new StructuredColumn(ColumnType::STRUCTURED, dbType: $dbType, columns: $columns);
    }
}
