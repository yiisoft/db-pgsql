<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests\Provider;

final class CompositeTypeSchemaProvider extends \Yiisoft\Db\Tests\Provider\SchemaProvider
{
    public static function columns(): array
    {
        return [
            [
                [
                    'id' => [
                        'type' => 'integer',
                        'dbType' => 'int4',
                        'phpType' => 'integer',
                        'primaryKey' => true,
                        'allowNull' => false,
                        'autoIncrement' => true,
                        'enumValues' => null,
                        'size' => null,
                        'precision' => 32,
                        'scale' => 0,
                        'defaultValue' => null,
                    ],
                    'price_col' => [
                        'type' => 'composite',
                        'dbType' => 'currency_money_composite',
                        'phpType' => 'array',
                        'primaryKey' => false,
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'precision' => null,
                        'scale' => null,
                        'defaultValue' => null,
                        'columns' => [
                            'value' => [
                                'type' => 'decimal',
                                'dbType' => 'numeric',
                                'phpType' => 'double',
                                'primaryKey' => false,
                                'allowNull' => true,
                                'autoIncrement' => false,
                                'enumValues' => null,
                                'size' => null,
                                'precision' => 10,
                                'scale' => 2,
                                'defaultValue' => null,
                            ],
                            'currency_code' => [
                                'type' => 'char',
                                'dbType' => 'bpchar',
                                'phpType' => 'string',
                                'primaryKey' => false,
                                'allowNull' => true,
                                'autoIncrement' => false,
                                'enumValues' => null,
                                'size' => 3,
                                'precision' => null,
                                'scale' => null,
                                'defaultValue' => null,
                            ],
                        ],
                    ],
                    'price_default' => [
                        'type' => 'composite',
                        'dbType' => 'currency_money_composite',
                        'phpType' => 'array',
                        'primaryKey' => false,
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'precision' => null,
                        'scale' => null,
                        'defaultValue' => ['value' => 5.0, 'currency_code' => 'USD'],
                        'columns' => [
                            'value' => [
                                'type' => 'decimal',
                                'dbType' => 'numeric',
                                'phpType' => 'double',
                                'primaryKey' => false,
                                'allowNull' => true,
                                'autoIncrement' => false,
                                'enumValues' => null,
                                'size' => null,
                                'precision' => 10,
                                'scale' => 2,
                                'defaultValue' => null,
                            ],
                            'currency_code' => [
                                'type' => 'char',
                                'dbType' => 'bpchar',
                                'phpType' => 'string',
                                'primaryKey' => false,
                                'allowNull' => true,
                                'autoIncrement' => false,
                                'enumValues' => null,
                                'size' => 3,
                                'precision' => null,
                                'scale' => null,
                                'defaultValue' => null,
                            ],
                        ],
                    ],
                    'price_array' => [
                        'type' => 'composite',
                        'dbType' => 'currency_money_composite',
                        'phpType' => 'array',
                        'primaryKey' => false,
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'precision' => null,
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
                                'phpType' => 'double',
                                'primaryKey' => false,
                                'allowNull' => true,
                                'autoIncrement' => false,
                                'enumValues' => null,
                                'size' => null,
                                'precision' => 10,
                                'scale' => 2,
                                'defaultValue' => null,
                            ],
                            'currency_code' => [
                                'type' => 'char',
                                'dbType' => 'bpchar',
                                'phpType' => 'string',
                                'primaryKey' => false,
                                'allowNull' => true,
                                'autoIncrement' => false,
                                'enumValues' => null,
                                'size' => 3,
                                'precision' => null,
                                'scale' => null,
                                'defaultValue' => null,
                            ],
                        ],
                    ],
                    'range_price_col' => [
                        'type' => 'composite',
                        'dbType' => 'range_price_composite',
                        'phpType' => 'array',
                        'primaryKey' => false,
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'precision' => null,
                        'scale' => null,
                        'defaultValue' => [
                            'price_from' => ['value' => 0.0, 'currency_code' => 'USD'],
                            'price_to' => ['value' => 100.0, 'currency_code' => 'USD'],
                        ],
                        'dimension' => 0,
                        'columns' => [
                            'price_from' => [
                                'type' => 'composite',
                                'dbType' => 'currency_money_composite',
                                'phpType' => 'array',
                                'primaryKey' => false,
                                'allowNull' => true,
                                'autoIncrement' => false,
                                'enumValues' => null,
                                'size' => null,
                                'precision' => null,
                                'scale' => null,
                                'defaultValue' => null,
                                'columns' => [
                                    'value' => [
                                        'type' => 'decimal',
                                        'dbType' => 'numeric',
                                        'phpType' => 'double',
                                        'primaryKey' => false,
                                        'allowNull' => true,
                                        'autoIncrement' => false,
                                        'enumValues' => null,
                                        'size' => null,
                                        'precision' => 10,
                                        'scale' => 2,
                                        'defaultValue' => null,
                                    ],
                                    'currency_code' => [
                                        'type' => 'char',
                                        'dbType' => 'bpchar',
                                        'phpType' => 'string',
                                        'primaryKey' => false,
                                        'allowNull' => true,
                                        'autoIncrement' => false,
                                        'enumValues' => null,
                                        'size' => 3,
                                        'precision' => null,
                                        'scale' => null,
                                        'defaultValue' => null,
                                    ],
                                ],
                            ],
                            'price_to' => [
                                'type' => 'composite',
                                'dbType' => 'currency_money_composite',
                                'phpType' => 'array',
                                'primaryKey' => false,
                                'allowNull' => true,
                                'autoIncrement' => false,
                                'enumValues' => null,
                                'size' => null,
                                'precision' => null,
                                'scale' => null,
                                'defaultValue' => null,
                                'columns' => [
                                    'value' => [
                                        'type' => 'decimal',
                                        'dbType' => 'numeric',
                                        'phpType' => 'double',
                                        'primaryKey' => false,
                                        'allowNull' => true,
                                        'autoIncrement' => false,
                                        'enumValues' => null,
                                        'size' => null,
                                        'precision' => 10,
                                        'scale' => 2,
                                        'defaultValue' => null,
                                    ],
                                    'currency_code' => [
                                        'type' => 'char',
                                        'dbType' => 'bpchar',
                                        'phpType' => 'string',
                                        'primaryKey' => false,
                                        'allowNull' => true,
                                        'autoIncrement' => false,
                                        'enumValues' => null,
                                        'size' => 3,
                                        'precision' => null,
                                        'scale' => null,
                                        'defaultValue' => null,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'test_composite_type',
            ],
        ];
    }
}
