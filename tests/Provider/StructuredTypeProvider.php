<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests\Provider;

final class StructuredTypeProvider
{
    public static function columns(): array
    {
        return [
            [
                [
                    'id' => [
                        'type' => 'integer',
                        'dbType' => 'int4',
                        'phpType' => 'int',
                        'primaryKey' => true,
                        'notNull' => true,
                        'autoIncrement' => true,
                        'enumValues' => null,
                        'size' => null,
                        'scale' => 0,
                        'defaultValue' => null,
                    ],
                    'price_col' => [
                        'type' => 'structured',
                        'dbType' => 'currency_money_structured',
                        'phpType' => 'array',
                        'primaryKey' => false,
                        'notNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'scale' => null,
                        'defaultValue' => null,
                        'columns' => [
                            'value' => [
                                'type' => 'decimal',
                                'dbType' => 'numeric',
                                'phpType' => 'float',
                                'primaryKey' => false,
                                'notNull' => false,
                                'autoIncrement' => false,
                                'enumValues' => null,
                                'size' => 10,
                                'scale' => 2,
                                'defaultValue' => null,
                            ],
                            'currency_code' => [
                                'type' => 'char',
                                'dbType' => 'bpchar',
                                'phpType' => 'string',
                                'primaryKey' => false,
                                'notNull' => false,
                                'autoIncrement' => false,
                                'enumValues' => null,
                                'size' => 3,
                                'scale' => null,
                                'defaultValue' => null,
                            ],
                        ],
                    ],
                    'price_default' => [
                        'type' => 'structured',
                        'dbType' => 'currency_money_structured',
                        'phpType' => 'array',
                        'primaryKey' => false,
                        'notNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'scale' => null,
                        'defaultValue' => ['value' => 5.0, 'currency_code' => 'USD'],
                        'columns' => [
                            'value' => [
                                'type' => 'decimal',
                                'dbType' => 'numeric',
                                'phpType' => 'float',
                                'primaryKey' => false,
                                'notNull' => false,
                                'autoIncrement' => false,
                                'enumValues' => null,
                                'size' => 10,
                                'scale' => 2,
                                'defaultValue' => null,
                            ],
                            'currency_code' => [
                                'type' => 'char',
                                'dbType' => 'bpchar',
                                'phpType' => 'string',
                                'primaryKey' => false,
                                'notNull' => false,
                                'autoIncrement' => false,
                                'enumValues' => null,
                                'size' => 3,
                                'scale' => null,
                                'defaultValue' => null,
                            ],
                        ],
                    ],
                    'price_array' => [
                        'type' => 'array',
                        'dbType' => 'currency_money_structured',
                        'phpType' => 'array',
                        'primaryKey' => false,
                        'notNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'scale' => null,
                        'defaultValue' => [
                            null,
                            ['value' => 10.55, 'currency_code' => 'USD'],
                            ['value' => -1.0, 'currency_code' => null],
                        ],
                        'dimension' => 1,
                        'columns' => [
                            'value' => [
                                'type' => 'decimal',
                                'dbType' => 'numeric',
                                'phpType' => 'float',
                                'primaryKey' => false,
                                'notNull' => false,
                                'autoIncrement' => false,
                                'enumValues' => null,
                                'size' => 10,
                                'scale' => 2,
                                'defaultValue' => null,
                            ],
                            'currency_code' => [
                                'type' => 'char',
                                'dbType' => 'bpchar',
                                'phpType' => 'string',
                                'primaryKey' => false,
                                'notNull' => false,
                                'autoIncrement' => false,
                                'enumValues' => null,
                                'size' => 3,
                                'scale' => null,
                                'defaultValue' => null,
                            ],
                        ],
                    ],
                    'price_array2' => [
                        'type' => 'array',
                        'dbType' => 'currency_money_structured',
                        'phpType' => 'array',
                        'primaryKey' => false,
                        'notNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'scale' => null,
                        'defaultValue' => null,
                        'dimension' => 2,
                        'columns' => [
                            'value' => [
                                'type' => 'decimal',
                                'dbType' => 'numeric',
                                'phpType' => 'float',
                                'primaryKey' => false,
                                'notNull' => false,
                                'autoIncrement' => false,
                                'enumValues' => null,
                                'size' => 10,
                                'scale' => 2,
                                'defaultValue' => null,
                            ],
                            'currency_code' => [
                                'type' => 'char',
                                'dbType' => 'bpchar',
                                'phpType' => 'string',
                                'primaryKey' => false,
                                'notNull' => false,
                                'autoIncrement' => false,
                                'enumValues' => null,
                                'size' => 3,
                                'scale' => null,
                                'defaultValue' => null,
                            ],
                        ],
                    ],
                    'range_price_col' => [
                        'type' => 'structured',
                        'dbType' => 'range_price_structured',
                        'phpType' => 'array',
                        'primaryKey' => false,
                        'notNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'scale' => null,
                        'defaultValue' => [
                            'price_from' => ['value' => 0.0, 'currency_code' => 'USD'],
                            'price_to' => ['value' => 100.0, 'currency_code' => 'USD'],
                        ],
                        'columns' => [
                            'price_from' => [
                                'type' => 'structured',
                                'dbType' => 'currency_money_structured',
                                'phpType' => 'array',
                                'primaryKey' => false,
                                'notNull' => false,
                                'autoIncrement' => false,
                                'enumValues' => null,
                                'size' => null,
                                'scale' => null,
                                'defaultValue' => null,
                                'columns' => [
                                    'value' => [
                                        'type' => 'decimal',
                                        'dbType' => 'numeric',
                                        'phpType' => 'float',
                                        'primaryKey' => false,
                                        'notNull' => false,
                                        'autoIncrement' => false,
                                        'enumValues' => null,
                                        'size' => 10,
                                        'scale' => 2,
                                        'defaultValue' => null,
                                    ],
                                    'currency_code' => [
                                        'type' => 'char',
                                        'dbType' => 'bpchar',
                                        'phpType' => 'string',
                                        'primaryKey' => false,
                                        'notNull' => false,
                                        'autoIncrement' => false,
                                        'enumValues' => null,
                                        'size' => 3,
                                        'scale' => null,
                                        'defaultValue' => null,
                                    ],
                                ],
                            ],
                            'price_to' => [
                                'type' => 'structured',
                                'dbType' => 'currency_money_structured',
                                'phpType' => 'array',
                                'primaryKey' => false,
                                'notNull' => false,
                                'autoIncrement' => false,
                                'enumValues' => null,
                                'size' => null,
                                'scale' => null,
                                'defaultValue' => null,
                                'columns' => [
                                    'value' => [
                                        'type' => 'decimal',
                                        'dbType' => 'numeric',
                                        'phpType' => 'float',
                                        'primaryKey' => false,
                                        'notNull' => false,
                                        'autoIncrement' => false,
                                        'enumValues' => null,
                                        'size' => 10,
                                        'scale' => 2,
                                        'defaultValue' => null,
                                    ],
                                    'currency_code' => [
                                        'type' => 'char',
                                        'dbType' => 'bpchar',
                                        'phpType' => 'string',
                                        'primaryKey' => false,
                                        'notNull' => false,
                                        'autoIncrement' => false,
                                        'enumValues' => null,
                                        'size' => 3,
                                        'scale' => null,
                                        'defaultValue' => null,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'test_structured_type',
            ],
        ];
    }
}
