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
                        autoIncrement: true,
                        dbType: 'int4',
                        primaryKey: true,
                        notNull: true,
                        scale: 0,
                        sequenceName: 'test_structured_type_id_seq',
                    ),
                    'price_col' => new StructuredColumn(
                        dbType: 'currency_money_structured',
                        defaultValue: null,
                        columns: [
                            'value' => new StringColumn(
                                ColumnType::DECIMAL,
                                dbType: 'numeric',
                                name: 'value',
                                notNull: false,
                                scale: 2,
                                size: 10,
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
                        defaultValue: ['value' => '5.00', 'currency_code' => 'USD'],
                        columns: [
                            'value' => new StringColumn(
                                ColumnType::DECIMAL,
                                dbType: 'numeric',
                                name: 'value',
                                notNull: false,
                                scale: 2,
                                size: 10,
                                defaultValue: '5.00',
                            ),
                            'currency_code' => new StringColumn(
                                ColumnType::CHAR,
                                dbType: 'bpchar',
                                name: 'currency_code',
                                notNull: false,
                                size: 3,
                                defaultValue: 'USD',
                            ),
                        ],
                    ),
                    'price_array' => new ArrayColumn(
                        dbType: 'currency_money_structured',
                        defaultValue: [
                            null,
                            ['value' => '10.55', 'currency_code' => 'USD'],
                            ['value' => '-1.00', 'currency_code' => null],
                        ],
                        dimension: 1,
                        column: new StructuredColumn(
                            dbType: 'currency_money_structured',
                            name: 'price_array',
                            notNull: false,
                            columns: [
                                'value' => new StringColumn(
                                    ColumnType::DECIMAL,
                                    dbType: 'numeric',
                                    name: 'value',
                                    notNull: false,
                                    scale: 2,
                                    size: 10,
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
                    ),
                    'price_array2' => new ArrayColumn(
                        dbType: 'currency_money_structured',
                        dimension: 2,
                        column: new StructuredColumn(
                            dbType: 'currency_money_structured',
                            name: 'price_array2',
                            notNull: false,
                            columns: [
                                'value' => new StringColumn(
                                    ColumnType::DECIMAL,
                                    dbType: 'numeric',
                                    name: 'value',
                                    notNull: false,
                                    scale: 2,
                                    size: 10,
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
                    ),
                    'range_price_col' => new StructuredColumn(
                        dbType: 'range_price_structured',
                        defaultValue: [
                            'price_from' => ['value' => '0.00', 'currency_code' => 'USD'],
                            'price_to' => ['value' => '100.00', 'currency_code' => 'USD'],
                        ],
                        columns: [
                            'price_from' => new StructuredColumn(
                                dbType: 'currency_money_structured',
                                name: 'price_from',
                                notNull: false,
                                defaultValue: ['value' => '0.00', 'currency_code' => 'USD'],
                                columns: [
                                    'value' => new StringColumn(
                                        ColumnType::DECIMAL,
                                        dbType: 'numeric',
                                        name: 'value',
                                        notNull: false,
                                        scale: 2,
                                        size: 10,
                                        defaultValue: '0.00'
                                    ),
                                    'currency_code' => new StringColumn(
                                        ColumnType::CHAR,
                                        dbType: 'bpchar',
                                        name: 'currency_code',
                                        notNull: false,
                                        size: 3,
                                        defaultValue: 'USD',
                                    ),
                                ],
                            ),
                            'price_to' => new StructuredColumn(
                                dbType: 'currency_money_structured',
                                name: 'price_to',
                                notNull: false,
                                defaultValue: ['value' => '100.00', 'currency_code' => 'USD'],
                                columns: [
                                    'value' => new StringColumn(
                                        ColumnType::DECIMAL,
                                        dbType: 'numeric',
                                        name: 'value',
                                        notNull: false,
                                        scale: 2,
                                        size: 10,
                                        defaultValue: '100.00',
                                    ),
                                    'currency_code' => new StringColumn(
                                        ColumnType::CHAR,
                                        dbType: 'bpchar',
                                        name: 'currency_code',
                                        notNull: false,
                                        size: 3,
                                        defaultValue: 'USD',
                                    ),
                                ],
                            ),
                        ],
                    ),
                ],
                'test_structured_type',
            ],
        ];
    }
}
