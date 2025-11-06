<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests\Provider;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Pgsql\Column\ArrayColumn;
use Yiisoft\Db\Pgsql\Column\BigBitColumn;
use Yiisoft\Db\Pgsql\Column\BinaryColumn;
use Yiisoft\Db\Pgsql\Column\BitColumn;
use Yiisoft\Db\Pgsql\Column\BooleanColumn;
use Yiisoft\Db\Pgsql\Column\IntegerColumn;
use Yiisoft\Db\Pgsql\Column\StructuredColumn;
use Yiisoft\Db\Schema\Column\DoubleColumn;

class ColumnBuilderProvider extends \Yiisoft\Db\Tests\Provider\ColumnBuilderProvider
{
    public static function buildingMethods(): array
    {
        $values = parent::buildingMethods();

        $values['primaryKey()'][2] = IntegerColumn::class;
        $values['primaryKey(false)'][2] = IntegerColumn::class;
        $values['smallPrimaryKey()'][2] = IntegerColumn::class;
        $values['smallPrimaryKey(false)'][2] = IntegerColumn::class;
        $values['bigPrimaryKey()'][2] = IntegerColumn::class;
        $values['bigPrimaryKey(false)'][2] = IntegerColumn::class;
        $values['boolean()'][2] = BooleanColumn::class;
        $values['bit()'][2] = BitColumn::class;
        $values['bit(1)'][2] = BitColumn::class;
        $values['bit(64)'] = ['bit', [64], BigBitColumn::class, ColumnType::BIT, ['getSize' => 64]];
        $values['tinyint()'][2] = IntegerColumn::class;
        $values['tinyint(1)'][2] = IntegerColumn::class;
        $values['smallint()'][2] = IntegerColumn::class;
        $values['smallint(1)'][2] = IntegerColumn::class;
        $values['integer()'][2] = IntegerColumn::class;
        $values['integer(1)'][2] = IntegerColumn::class;
        $values['bigint()'][2] = IntegerColumn::class;
        $values['bigint(1)'][2] = IntegerColumn::class;
        $values['float()'][2] = DoubleColumn::class;
        $values['float(8)'][2] = DoubleColumn::class;
        $values['float(8,2)'][2] = DoubleColumn::class;
        $values['double()'][2] = DoubleColumn::class;
        $values['double(8)'][2] = DoubleColumn::class;
        $values['double(8,2)'][2] = DoubleColumn::class;
        $values['decimal()'][2] = DoubleColumn::class;
        $values['decimal(8)'][2] = DoubleColumn::class;
        $values['decimal(8,2)'][2] = DoubleColumn::class;
        $values['money()'][2] = DoubleColumn::class;
        $values['money(8)'][2] = DoubleColumn::class;
        $values['money(8,2)'][2] = DoubleColumn::class;
        $values['binary()'][2] = BinaryColumn::class;
        $values['binary(8)'][2] = BinaryColumn::class;
        $values['array()'][2] = ArrayColumn::class;
        $values['array($column)'][2] = ArrayColumn::class;
        $values['structured()'][2] = StructuredColumn::class;
        $values["structured('money_currency')"][2] = StructuredColumn::class;
        $values["structured('money_currency',\$columns)"][2] = StructuredColumn::class;

        return $values;
    }
}
