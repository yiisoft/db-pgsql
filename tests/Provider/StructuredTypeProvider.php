<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests\Provider;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Pgsql\Column\ArrayColumn;
use Yiisoft\Db\Pgsql\Column\IntegerColumn;
use Yiisoft\Db\Pgsql\Column\StructuredColumn;
use Yiisoft\Db\Schema\Column\DoubleColumn;
use Yiisoft\Db\Schema\Column\StringColumn;

final class StructuredTypeProvider
{
    public static function columns(): array
    {
        return [
            [
                [
                    'id' => new IntegerColumn(
                        dbType: 'int4',
                        primaryKey: true,
                        notNull: true,
                        autoIncrement: true,
                        sequenceName: 'test_structured_type_id_seq',
                        scale: 0,
                    ),
                    'price_col' => new StructuredColumn(
                        dbType: 'currency_money_structured',
                        defaultValue: null,
                        columns: [
                            'value' => new DoubleColumn(
                                ColumnType::DECIMAL,
                                dbType: 'numeric',
                                name: 'value',
                                notNull: false,
                                size: 10,
                                scale: 2,
                                defaultValue: null,
                            ),
                            'currency_code' => new StringColumn(
                                ColumnType::CHAR,
                                dbType: 'bpchar',
                                name: 'currency_code',
                                notNull: false,
                                size: 3,
                                defaultValue: null,
                            ),
                        ],
                    ),
                    'price_default' => new StructuredColumn(
                        dbType: 'currency_money_structured',
                        defaultValue: ['value' => 5.0, 'currency_code' => 'USD'],
                        columns: [
                            'value' => new DoubleColumn(
                                ColumnType::DECIMAL,
                                dbType: 'numeric',
                                defaultValue: 5.0,
                                name: 'value',
                                notNull: false,
                                size: 10,
                                scale: 2,
                            ),
                            'currency_code' => new StringColumn(
                                ColumnType::CHAR,
                                dbType: 'bpchar',
                                defaultValue: 'USD',
                                name: 'currency_code',
                                notNull: false,
                                size: 3,
                            ),
                        ],
                    ),
                    'price_array' => new ArrayColumn(
                        dbType: 'currency_money_structured',
                        defaultValue: [
                            null,
                            ['value' => 10.55, 'currency_code' => 'USD'],
                            ['value' => -1.0, 'currency_code' => null],
                        ],
                        dimension: 1,
                        column: new StructuredColumn(
                            dbType: 'currency_money_structured',
                            columns: [
                                'value' => new DoubleColumn(
                                    ColumnType::DECIMAL,
                                    dbType: 'numeric',
                                    name: 'value',
                                    notNull: false,
                                    size: 10,
                                    scale: 2,
                                    defaultValue: null,
                                ),
                                'currency_code' => new StringColumn(
                                    ColumnType::CHAR,
                                    dbType: 'bpchar',
                                    name: 'currency_code',
                                    notNull: false,
                                    size: 3,
                                    defaultValue: null,
                                ),
                            ],
                            name: 'price_array',
                            notNull: false,
                        ),
                    ),
                    'price_array2' => new ArrayColumn(
                        dbType: 'currency_money_structured',
                        dimension: 2,
                        column: new StructuredColumn(
                            dbType: 'currency_money_structured',
                            defaultValue: null,
                            columns: [
                                'value' => new DoubleColumn(
                                    ColumnType::DECIMAL,
                                    dbType: 'numeric',
                                    name: 'value',
                                    notNull: false,
                                    size: 10,
                                    scale: 2,
                                    defaultValue: null,
                                ),
                                'currency_code' => new StringColumn(
                                    ColumnType::CHAR,
                                    dbType: 'bpchar',
                                    name: 'currency_code',
                                    notNull: false,
                                    size: 3,
                                    defaultValue: null,
                                ),
                            ],
                            name: 'price_array2',
                            notNull: false,
                        ),
                    ),
                    'range_price_col' => new StructuredColumn(
                        dbType: 'range_price_structured',
                        defaultValue: [
                            'price_from' => ['value' => 0.0, 'currency_code' => 'USD'],
                            'price_to' => ['value' => 100.0, 'currency_code' => 'USD'],
                        ],
                        columns: [
                            'price_from' => new StructuredColumn(
                                dbType: 'currency_money_structured',
                                defaultValue: null,
                                columns: [
                                    'value' => new DoubleColumn(
                                        ColumnType::DECIMAL,
                                        dbType: 'numeric',
                                        name: 'value',
                                        notNull: false,
                                        size: 10,
                                        scale: 2,
                                        defaultValue: null,
                                    ),
                                    'currency_code' => new StringColumn(
                                        ColumnType::CHAR,
                                        dbType: 'bpchar',
                                        name: 'currency_code',
                                        notNull: false,
                                        size: 3,
                                        defaultValue: null,
                                    ),
                                ],
                                name: 'price_from',
                                notNull: false,
                            ),
                            'price_to' => new StructuredColumn(
                                dbType: 'currency_money_structured',
                                defaultValue: null,
                                columns: [
                                    'value' => new DoubleColumn(
                                        ColumnType::DECIMAL,
                                        dbType: 'numeric',
                                        name: 'value',
                                        notNull: false,
                                        size: 10,
                                        scale: 2,
                                        defaultValue: null,
                                    ),
                                    'currency_code' => new StringColumn(
                                        ColumnType::CHAR,
                                        dbType: 'bpchar',
                                        name: 'currency_code',
                                        notNull: false,
                                        size: 3,
                                        defaultValue: null,
                                    ),
                                ],
                                name: 'price_to',
                                notNull: false,
                            ),
                        ],
                    ),
                ],
                'test_structured_type',
            ],
        ];
    }
}
