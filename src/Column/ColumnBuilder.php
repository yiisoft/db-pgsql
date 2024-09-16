<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Column;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Schema\Column\ColumnSchemaInterface;

final class ColumnBuilder extends \Yiisoft\Db\Schema\Column\ColumnBuilder
{
    public static function boolean(): ColumnSchemaInterface
    {
        return (new BooleanColumnSchema(ColumnType::BOOLEAN));
    }

    public static function bit(int|null $size = null): ColumnSchemaInterface
    {
        return (new BitColumnSchema(ColumnType::BIT))
            ->size($size);
    }

    public static function tinyint(int|null $size = null): ColumnSchemaInterface
    {
        return (new IntegerColumnSchema(ColumnType::TINYINT))
            ->size($size);
    }

    public static function smallint(int|null $size = null): ColumnSchemaInterface
    {
        return (new IntegerColumnSchema(ColumnType::SMALLINT))
            ->size($size);
    }

    public static function integer(int|null $size = null): ColumnSchemaInterface
    {
        return (new IntegerColumnSchema(ColumnType::INTEGER))
            ->size($size);
    }

    public static function bigint(int|null $size = null): ColumnSchemaInterface
    {
        return (new IntegerColumnSchema(ColumnType::BIGINT))
            ->size($size);
    }

    public static function binary(int|null $size = null): ColumnSchemaInterface
    {
        return (new BinaryColumnSchema(ColumnType::BINARY))
            ->size($size);
    }

    public static function array(ColumnSchemaInterface|null $column = null): ColumnSchemaInterface
    {
        return (new ArrayColumnSchema(ColumnType::ARRAY))
            ->column($column);
    }

    /**
     * @param string|null $dbType The DB type of the column.
     * @param ColumnSchemaInterface[] $columns The columns (name -> instance) that the structured column should contain.
     *
     * @psalm-param array<string, ColumnSchemaInterface> $columns
     */
    public static function structured(string|null $dbType = null, array $columns = []): ColumnSchemaInterface
    {
        return (new StructuredColumnSchema(ColumnType::STRUCTURED))
            ->dbType($dbType)
            ->columns($columns);
    }
}
