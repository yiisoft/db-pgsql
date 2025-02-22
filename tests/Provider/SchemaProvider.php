<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests\Provider;

use Yiisoft\Db\Expression\Expression;

final class SchemaProvider extends \Yiisoft\Db\Tests\Provider\SchemaProvider
{
    public static function columns(): array
    {
        return [
            [
                [
                    'int_col' => [
                        'type' => 'integer',
                        'dbType' => 'int4',
                        'phpType' => 'int',
                        'primaryKey' => false,
                        'notNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'scale' => 0,
                        'defaultValue' => null,
                    ],
                    'int_col2' => [
                        'type' => 'integer',
                        'dbType' => 'int4',
                        'phpType' => 'int',
                        'primaryKey' => false,
                        'notNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'scale' => 0,
                        'defaultValue' => 1,
                    ],
                    'tinyint_col' => [
                        'type' => 'smallint',
                        'dbType' => 'int2',
                        'phpType' => 'int',
                        'primaryKey' => false,
                        'notNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'scale' => 0,
                        'defaultValue' => 1,
                    ],
                    'smallint_col' => [
                        'type' => 'smallint',
                        'dbType' => 'int2',
                        'phpType' => 'int',
                        'primaryKey' => false,
                        'notNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'scale' => 0,
                        'defaultValue' => 1,
                    ],
                    'char_col' => [
                        'type' => 'char',
                        'dbType' => 'bpchar',
                        'phpType' => 'string',
                        'primaryKey' => false,
                        'notNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 100,
                        'scale' => null,
                        'defaultValue' => null,
                    ],
                    'char_col2' => [
                        'type' => 'string',
                        'dbType' => 'varchar',
                        'phpType' => 'string',
                        'primaryKey' => false,
                        'notNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 100,
                        'scale' => null,
                        'defaultValue' => 'some\'thing',
                    ],
                    'char_col3' => [
                        'type' => 'text',
                        'dbType' => 'text',
                        'phpType' => 'string',
                        'primaryKey' => false,
                        'notNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'scale' => null,
                        'defaultValue' => null,
                    ],
                    'char_col4' => [
                        'type' => 'string',
                        'dbType' => 'varchar',
                        'phpType' => 'string',
                        'primaryKey' => false,
                        'notNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'scale' => null,
                        'defaultValue' => "first line\nsecond line",
                    ],
                    'float_col' => [
                        'type' => 'double',
                        'dbType' => 'float8',
                        'phpType' => 'float',
                        'primaryKey' => false,
                        'notNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'scale' => null,
                        'defaultValue' => null,
                    ],
                    'float_col2' => [
                        'type' => 'double',
                        'dbType' => 'float8',
                        'phpType' => 'float',
                        'primaryKey' => false,
                        'notNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'scale' => null,
                        'defaultValue' => 1.23,
                    ],
                    'blob_col' => [
                        'type' => 'binary',
                        'dbType' => 'bytea',
                        'phpType' => 'mixed',
                        'primaryKey' => false,
                        'notNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'scale' => null,
                        'defaultValue' => 'a binary value',
                    ],
                    'numeric_col' => [
                        'type' => 'decimal',
                        'dbType' => 'numeric',
                        'phpType' => 'float',
                        'primaryKey' => false,
                        'notNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 5,
                        'scale' => 2,
                        'defaultValue' => 33.22,
                    ],
                    'time' => [
                        'type' => 'timestamp',
                        'dbType' => 'timestamp',
                        'phpType' => 'string',
                        'primaryKey' => false,
                        'notNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 6,
                        'scale' => null,
                        'defaultValue' => '2002-01-01 00:00:00',
                    ],
                    'bool_col' => [
                        'type' => 'boolean',
                        'dbType' => 'bool',
                        'phpType' => 'bool',
                        'primaryKey' => false,
                        'notNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'scale' => null,
                        'defaultValue' => null,
                    ],
                    'bool_col2' => [
                        'type' => 'boolean',
                        'dbType' => 'bool',
                        'phpType' => 'bool',
                        'primaryKey' => false,
                        'notNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'scale' => null,
                        'defaultValue' => true,
                    ],
                    'ts_default' => [
                        'type' => 'timestamp',
                        'dbType' => 'timestamp',
                        'phpType' => 'string',
                        'primaryKey' => false,
                        'notNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 6,
                        'scale' => null,
                        'defaultValue' => new Expression('now()'),
                    ],
                    'bit_col' => [
                        'type' => 'bit',
                        'dbType' => 'bit',
                        'phpType' => 'int',
                        'primaryKey' => false,
                        'notNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 8,
                        'scale' => null,
                        'defaultValue' => 0b1000_0010, // 130
                    ],
                    'varbit_col' => [
                        'type' => 'bit',
                        'dbType' => 'varbit',
                        'phpType' => 'int',
                        'primaryKey' => false,
                        'notNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'scale' => null,
                        'defaultValue' => 0b100, // 4
                    ],
                    'bigint_col' => [
                        'type' => 'bigint',
                        'dbType' => 'int8',
                        'phpType' => 'int',
                        'primaryKey' => false,
                        'notNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'scale' => 0,
                        'defaultValue' => null,
                    ],
                    'intarray_col' => [
                        'type' => 'array',
                        'dbType' => 'int4',
                        'phpType' => 'array',
                        'primaryKey' => false,
                        'notNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'scale' => 0,
                        'defaultValue' => null,
                        'dimension' => 1,
                        'column' => [
                            'type' => 'integer',
                            'dbType' => 'int4',
                            'phpType' => 'int',
                            'enumValues' => null,
                            'size' => null,
                            'scale' => 0,
                        ],
                    ],
                    'numericarray_col' => [
                        'type' => 'array',
                        'dbType' => 'numeric',
                        'phpType' => 'array',
                        'primaryKey' => false,
                        'notNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 5,
                        'scale' => 2,
                        'defaultValue' => null,
                        'dimension' => 1,
                        'column' => [
                            'type' => 'decimal',
                            'dbType' => 'numeric',
                            'phpType' => 'float',
                            'enumValues' => null,
                            'size' => 5,
                            'scale' => 2,
                        ],
                    ],
                    'varchararray_col' => [
                        'type' => 'array',
                        'dbType' => 'varchar',
                        'phpType' => 'array',
                        'primaryKey' => false,
                        'notNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 100,
                        'scale' => null,
                        'defaultValue' => null,
                        'dimension' => 1,
                        'column' => [
                            'type' => 'string',
                            'dbType' => 'varchar',
                            'phpType' => 'string',
                            'enumValues' => null,
                            'size' => 100,
                            'scale' => null,
                        ],
                    ],
                    'textarray2_col' => [
                        'type' => 'array',
                        'dbType' => 'text',
                        'phpType' => 'array',
                        'primaryKey' => false,
                        'notNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'scale' => null,
                        'defaultValue' => null,
                        'dimension' => 2,
                        'column' => [
                            'type' => 'text',
                            'dbType' => 'text',
                            'phpType' => 'string',
                            'enumValues' => null,
                            'size' => null,
                            'scale' => null,
                        ],
                    ],
                    'json_col' => [
                        'type' => 'json',
                        'dbType' => 'json',
                        'phpType' => 'mixed',
                        'primaryKey' => false,
                        'notNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'scale' => null,
                        'defaultValue' => ['a' => 1],
                    ],
                    'jsonb_col' => [
                        'type' => 'json',
                        'dbType' => 'jsonb',
                        'phpType' => 'mixed',
                        'primaryKey' => false,
                        'notNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'scale' => null,
                        'defaultValue' => null,
                    ],
                    'jsonarray_col' => [
                        'type' => 'array',
                        'dbType' => 'json',
                        'phpType' => 'array',
                        'primaryKey' => false,
                        'notNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'scale' => null,
                        'defaultValue' => null,
                        'dimension' => 1,
                        'column' => [
                            'type' => 'json',
                            'dbType' => 'json',
                            'phpType' => 'mixed',
                            'enumValues' => null,
                            'size' => null,
                            'scale' => null,
                        ],
                    ],
                ],
                'tableName' => 'type',
            ],
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
                    'type' => [
                        'type' => 'string',
                        'dbType' => 'varchar',
                        'phpType' => 'string',
                        'primaryKey' => false,
                        'notNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 255,
                        'scale' => null,
                        'defaultValue' => null,
                    ],
                ],
                'animal',
            ],
            [
                [
                    'uuid' => [
                        'type' => 'string',
                        'dbType' => 'uuid',
                        'phpType' => 'string',
                        'primaryKey' => true,
                        'notNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'scale' => null,
                        'defaultValue' => null,
                    ],
                    'col' => [
                        'type' => 'string',
                        'dbType' => 'varchar',
                        'phpType' => 'string',
                        'primaryKey' => false,
                        'notNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 16,
                        'scale' => null,
                        'defaultValue' => null,
                    ],
                ],
                'table_uuid',
            ],
            [
                [
                    'C_id' => [
                        'type' => 'integer',
                        'dbType' => 'int4',
                        'phpType' => 'int',
                        'primaryKey' => true,
                        'notNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'scale' => 0,
                        'defaultValue' => null,
                        'unique' => false,
                    ],
                    'C_not_null' => [
                        'type' => 'integer',
                        'dbType' => 'int4',
                        'phpType' => 'int',
                        'primaryKey' => false,
                        'notNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'scale' => 0,
                        'defaultValue' => null,
                        'unique' => false,
                    ],
                    'C_check' => [
                        'type' => 'string',
                        'dbType' => 'varchar',
                        'phpType' => 'string',
                        'primaryKey' => false,
                        'notNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 255,
                        'scale' => null,
                        'defaultValue' => null,
                        'unique' => false,
                    ],
                    'C_unique' => [
                        'type' => 'integer',
                        'dbType' => 'int4',
                        'phpType' => 'int',
                        'primaryKey' => false,
                        'notNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'scale' => 0,
                        'defaultValue' => null,
                        'unique' => true,
                    ],
                    'C_default' => [
                        'type' => 'integer',
                        'dbType' => 'int4',
                        'phpType' => 'int',
                        'primaryKey' => false,
                        'notNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'scale' => 0,
                        'defaultValue' => 0,
                        'unique' => false,
                    ],
                ],
                'T_constraints_1',
            ],
        ];
    }

    public static function constraints(): array
    {
        $constraints = parent::constraints();

        $constraints['1: check'][2][0]->expression('CHECK ((("C_check")::text <> \'\'::text))');
        $constraints['3: foreign key'][2][0]->foreignSchemaName('public');
        $constraints['3: index'][2] = [];

        return $constraints;
    }

    public static function constraintsOfView(): array
    {
        $constraints = self::constraints();

        $result = [];

        foreach ($constraints as $key => $constraint) {
            $result['view ' . $key] = $constraint;
            $result['view ' . $key][0] = $constraint[0] . '_view';
        }

        return $result;
    }

    public static function tableSchemaCacheWithTablePrefixes(): array
    {
        $configs = [
            ['prefix' => '', 'name' => 'type'],
            ['prefix' => '', 'name' => '{{%type}}'],
            ['prefix' => 'ty', 'name' => '{{%pe}}'],
        ];

        $data = [];

        foreach ($configs as $config) {
            foreach ($configs as $testConfig) {
                if ($config === $testConfig) {
                    continue;
                }

                $description = sprintf(
                    "%s (with '%s' prefix) against %s (with '%s' prefix)",
                    $config['name'],
                    $config['prefix'],
                    $testConfig['name'],
                    $testConfig['prefix']
                );
                $data[$description] = [
                    $config['prefix'],
                    $config['name'],
                    $testConfig['prefix'],
                    $testConfig['name'],
                ];
            }
        }

        return $data;
    }

    public static function tableSchemaWithDbSchemes(): array
    {
        return [
            ['animal', 'animal', 'public'],
            ['public.animal', 'animal', 'public'],
            ['"public"."animal"', 'animal', 'public'],
            ['"other"."animal2"', 'animal2', 'other',],
            ['other."animal2"', 'animal2', 'other',],
            ['other.animal2', 'animal2', 'other',],
            ['catalog.other.animal2', 'animal2', 'other'],
        ];
    }
}
