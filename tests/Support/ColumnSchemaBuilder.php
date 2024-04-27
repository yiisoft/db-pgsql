<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests\Support;

use Yiisoft\Db\Schema\Column\ColumnSchemaInterface;
use Yiisoft\Db\Schema\Column\DoubleColumnSchema;
use Yiisoft\Db\Schema\Column\StringColumnSchema;
use Yiisoft\Db\Schema\SchemaInterface;

class ColumnSchemaBuilder
{
    public static function numeric(string $name, int|null $precision, int|null $scale, mixed $defaultValue = null): ColumnSchemaInterface
    {
        $column = new DoubleColumnSchema(SchemaInterface::TYPE_DECIMAL);
        $column->name($name);
        $column->dbType('numeric');
        $column->precision($precision);
        $column->scale($scale);
        $column->defaultValue($defaultValue);

        return $column;
    }

    public static function char(string $name, int|null $size, mixed $defaultValue = null): ColumnSchemaInterface
    {
        $column = new StringColumnSchema(SchemaInterface::TYPE_CHAR);
        $column->name($name);
        $column->dbType('bpchar');
        $column->size($size);
        $column->defaultValue($defaultValue);

        return $column;
    }
}
