<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests\Support;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Schema\Column\ColumnSchemaInterface;
use Yiisoft\Db\Schema\Column\DoubleColumnSchema;
use Yiisoft\Db\Schema\Column\StringColumnSchema;

class ColumnSchemaBuilder
{
    public static function numeric(string $name, int|null $size, int|null $scale, mixed $defaultValue = null): ColumnSchemaInterface
    {
        $column = new DoubleColumnSchema(ColumnType::DECIMAL);
        $column->name($name);
        $column->dbType('numeric');
        $column->size($size);
        $column->scale($scale);
        $column->defaultValue($defaultValue);

        return $column;
    }

    public static function char(string $name, int|null $size, mixed $defaultValue = null): ColumnSchemaInterface
    {
        $column = new StringColumnSchema(ColumnType::CHAR);
        $column->name($name);
        $column->dbType('bpchar');
        $column->size($size);
        $column->defaultValue($defaultValue);

        return $column;
    }
}
