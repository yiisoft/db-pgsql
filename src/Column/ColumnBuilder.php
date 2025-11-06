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

    public static function bit(?int $size = null): BitColumn
    {
        return new BitColumn(ColumnType::BIT, size: $size);
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

    public static function bigint(?int $size = null): IntegerColumn
    {
        return new IntegerColumn(ColumnType::BIGINT, size: $size);
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
}
