<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests\Provider;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Pgsql\Column\ArrayColumnSchema;
use Yiisoft\Db\Pgsql\Column\BinaryColumnSchema;
use Yiisoft\Db\Pgsql\Column\BitColumnSchema;
use Yiisoft\Db\Pgsql\Column\BooleanColumnSchema;
use Yiisoft\Db\Pgsql\Column\ColumnBuilder;
use Yiisoft\Db\Pgsql\Column\IntegerColumnSchema;
use Yiisoft\Db\Pgsql\Column\StructuredColumnSchema;
use Yiisoft\Db\Schema\Column\DoubleColumnSchema;
use Yiisoft\Db\Schema\Column\StringColumnSchema;

class ColumnBuilderProvider extends \Yiisoft\Db\Tests\Provider\ColumnBuilderProvider
{
    public static function buildingMethods(): array
    {
        return [
            // building method, args, expected instance of, expected type, expected column method results
            ...parent::buildingMethods(),
            ['primaryKey', [], IntegerColumnSchema::class, ColumnType::INTEGER, ['isPrimaryKey' => true, 'isAutoIncrement' => true]],
            ['primaryKey', [false], IntegerColumnSchema::class, ColumnType::INTEGER, ['isPrimaryKey' => true, 'isAutoIncrement' => false]],
            ['smallPrimaryKey', [], IntegerColumnSchema::class, ColumnType::SMALLINT, ['isPrimaryKey' => true, 'isAutoIncrement' => true]],
            ['smallPrimaryKey', [false], IntegerColumnSchema::class, ColumnType::SMALLINT, ['isPrimaryKey' => true, 'isAutoIncrement' => false]],
            ['bigPrimaryKey', [], IntegerColumnSchema::class, ColumnType::BIGINT, ['isPrimaryKey' => true, 'isAutoIncrement' => true]],
            ['bigPrimaryKey', [false], IntegerColumnSchema::class, ColumnType::BIGINT, ['isPrimaryKey' => true, 'isAutoIncrement' => false]],
            ['boolean', [], BooleanColumnSchema::class, ColumnType::BOOLEAN],
            ['bit', [], BitColumnSchema::class, ColumnType::BIT],
            ['bit', [1], BitColumnSchema::class, ColumnType::BIT, ['getSize' => 1]],
            ['tinyint', [], IntegerColumnSchema::class, ColumnType::TINYINT],
            ['tinyint', [1], IntegerColumnSchema::class, ColumnType::TINYINT, ['getSize' => 1]],
            ['smallint', [], IntegerColumnSchema::class, ColumnType::SMALLINT],
            ['smallint', [1], IntegerColumnSchema::class, ColumnType::SMALLINT, ['getSize' => 1]],
            ['integer', [], IntegerColumnSchema::class, ColumnType::INTEGER],
            ['integer', [1], IntegerColumnSchema::class, ColumnType::INTEGER, ['getSize' => 1]],
            ['bigint', [], IntegerColumnSchema::class, ColumnType::BIGINT],
            ['bigint', [1], IntegerColumnSchema::class, ColumnType::BIGINT, ['getSize' => 1]],
            ['float', [], DoubleColumnSchema::class, ColumnType::FLOAT],
            ['float', [8], DoubleColumnSchema::class, ColumnType::FLOAT, ['getSize' => 8]],
            ['float', [8, 2], DoubleColumnSchema::class, ColumnType::FLOAT, ['getSize' => 8, 'getScale' => 2]],
            ['double', [], DoubleColumnSchema::class, ColumnType::DOUBLE],
            ['double', [8], DoubleColumnSchema::class, ColumnType::DOUBLE, ['getSize' => 8]],
            ['double', [8, 2], DoubleColumnSchema::class, ColumnType::DOUBLE, ['getSize' => 8, 'getScale' => 2]],
            ['decimal', [], DoubleColumnSchema::class, ColumnType::DECIMAL, ['getSize' => 10, 'getScale' => 0]],
            ['decimal', [8], DoubleColumnSchema::class, ColumnType::DECIMAL, ['getSize' => 8, 'getScale' => 0]],
            ['decimal', [8, 2], DoubleColumnSchema::class, ColumnType::DECIMAL, ['getSize' => 8, 'getScale' => 2]],
            ['money', [], DoubleColumnSchema::class, ColumnType::MONEY, ['getSize' => 19, 'getScale' => 4]],
            ['money', [8], DoubleColumnSchema::class, ColumnType::MONEY, ['getSize' => 8, 'getScale' => 4]],
            ['money', [8, 2], DoubleColumnSchema::class, ColumnType::MONEY, ['getSize' => 8, 'getScale' => 2]],
            ['binary', [], BinaryColumnSchema::class, ColumnType::BINARY],
            ['binary', [8], BinaryColumnSchema::class, ColumnType::BINARY, ['getSize' => 8]],
            ['array', [], ArrayColumnSchema::class, ColumnType::ARRAY],
            ['array', [$column = new StringColumnSchema()], ArrayColumnSchema::class, ColumnType::ARRAY, ['getColumn' => $column]],
            ['structured', [], StructuredColumnSchema::class, ColumnType::STRUCTURED],
            ['structured', ['money_currency'], StructuredColumnSchema::class, ColumnType::STRUCTURED, ['getDbType' => 'money_currency']],
            [
                'structured',
                [
                    'money_currency',
                    $columns = ['value' => ColumnBuilder::money(), 'currency' => ColumnBuilder::string(3)],
                ],
                StructuredColumnSchema::class,
                ColumnType::STRUCTURED,
                ['getDbType' => 'money_currency', 'getColumns' => $columns],
            ],
        ];
    }
}
