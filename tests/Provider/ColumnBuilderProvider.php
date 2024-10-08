<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests\Provider;

use Yiisoft\Db\Pgsql\Column\ArrayColumnSchema;
use Yiisoft\Db\Pgsql\Column\BinaryColumnSchema;
use Yiisoft\Db\Pgsql\Column\BitColumnSchema;
use Yiisoft\Db\Pgsql\Column\BooleanColumnSchema;
use Yiisoft\Db\Pgsql\Column\IntegerColumnSchema;
use Yiisoft\Db\Pgsql\Column\StructuredColumnSchema;
use Yiisoft\Db\Schema\Column\DoubleColumnSchema;

class ColumnBuilderProvider extends \Yiisoft\Db\Tests\Provider\ColumnBuilderProvider
{
    public static function buildingMethods(): array
    {
        $values = parent::buildingMethods();

        $values['primaryKey()'][2] = IntegerColumnSchema::class;
        $values['primaryKey(false)'][2] = IntegerColumnSchema::class;
        $values['smallPrimaryKey()'][2] = IntegerColumnSchema::class;
        $values['smallPrimaryKey(false)'][2] = IntegerColumnSchema::class;
        $values['bigPrimaryKey()'][2] = IntegerColumnSchema::class;
        $values['bigPrimaryKey(false)'][2] = IntegerColumnSchema::class;
        $values['boolean()'][2] = BooleanColumnSchema::class;
        $values['bit()'][2] = BitColumnSchema::class;
        $values['bit(1)'][2] = BitColumnSchema::class;
        $values['tinyint()'][2] = IntegerColumnSchema::class;
        $values['tinyint(1)'][2] = IntegerColumnSchema::class;
        $values['smallint()'][2] = IntegerColumnSchema::class;
        $values['smallint(1)'][2] = IntegerColumnSchema::class;
        $values['integer()'][2] = IntegerColumnSchema::class;
        $values['integer(1)'][2] = IntegerColumnSchema::class;
        $values['bigint()'][2] = IntegerColumnSchema::class;
        $values['bigint(1)'][2] = IntegerColumnSchema::class;
        $values['float()'][2] = DoubleColumnSchema::class;
        $values['float(8)'][2] = DoubleColumnSchema::class;
        $values['float(8,2)'][2] = DoubleColumnSchema::class;
        $values['double()'][2] = DoubleColumnSchema::class;
        $values['double(8)'][2] = DoubleColumnSchema::class;
        $values['double(8,2)'][2] = DoubleColumnSchema::class;
        $values['decimal()'][2] = DoubleColumnSchema::class;
        $values['decimal(8)'][2] = DoubleColumnSchema::class;
        $values['decimal(8,2)'][2] = DoubleColumnSchema::class;
        $values['money()'][2] = DoubleColumnSchema::class;
        $values['money(8)'][2] = DoubleColumnSchema::class;
        $values['money(8,2)'][2] = DoubleColumnSchema::class;
        $values['binary()'][2] = BinaryColumnSchema::class;
        $values['binary(8)'][2] = BinaryColumnSchema::class;
        $values['array()'][2] = ArrayColumnSchema::class;
        $values['array($column)'][2] = ArrayColumnSchema::class;
        $values['structured()'][2] = StructuredColumnSchema::class;
        $values["structured('money_currency')"][2] = StructuredColumnSchema::class;
        $values["structured('money_currency',\$columns)"][2] = StructuredColumnSchema::class;

        return $values;
    }
}
