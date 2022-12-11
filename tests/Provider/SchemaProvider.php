<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests\Provider;

use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Tests\Provider\AbstractSchemaProvider;
use Yiisoft\Db\Tests\Support\AnyValue;

final class SchemaProvider extends AbstractSchemaProvider
{
    public function columns(): array
    {
        return [
            [
                [
                    'int_col' => [
                        'type' => 'integer',
                        'dbType' => 'int4',
                        'phpType' => 'integer',
                        'allowNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'precision' => 32,
                        'scale' => 0,
                        'defaultValue' => null,
                    ],
                    'int_col2' => [
                        'type' => 'integer',
                        'dbType' => 'int4',
                        'phpType' => 'integer',
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'precision' => 32,
                        'scale' => 0,
                        'defaultValue' => 1,
                    ],
                    'tinyint_col' => [
                        'type' => 'smallint',
                        'dbType' => 'int2',
                        'phpType' => 'integer',
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'precision' => 16,
                        'scale' => 0,
                        'defaultValue' => 1,
                    ],
                    'smallint_col' => [
                        'type' => 'smallint',
                        'dbType' => 'int2',
                        'phpType' => 'integer',
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'precision' => 16,
                        'scale' => 0,
                        'defaultValue' => 1,
                    ],
                    'char_col' => [
                        'type' => 'char',
                        'dbType' => 'bpchar',
                        'phpType' => 'string',
                        'allowNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 100,
                        'precision' => null,
                        'scale' => null,
                        'defaultValue' => null,
                    ],
                    'char_col2' => [
                        'type' => 'string',
                        'dbType' => 'varchar',
                        'phpType' => 'string',
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 100,
                        'precision' => null,
                        'scale' => null,
                        'defaultValue' => 'something',
                    ],
                    'char_col3' => [
                        'type' => 'text',
                        'dbType' => 'text',
                        'phpType' => 'string',
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'precision' => null,
                        'scale' => null,
                        'defaultValue' => null,
                    ],
                    'float_col' => [
                        'type' => 'double',
                        'dbType' => 'float8',
                        'phpType' => 'double',
                        'allowNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'precision' => 53,
                        'scale' => null,
                        'defaultValue' => null,
                    ],
                    'float_col2' => [
                        'type' => 'double',
                        'dbType' => 'float8',
                        'phpType' => 'double',
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'precision' => 53,
                        'scale' => null,
                        'defaultValue' => 1.23,
                    ],
                    'blob_col' => [
                        'type' => 'binary',
                        'dbType' => 'bytea',
                        'phpType' => 'resource',
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'precision' => null,
                        'scale' => null,
                        'defaultValue' => null,
                    ],
                    'numeric_col' => [
                        'type' => 'decimal',
                        'dbType' => 'numeric',
                        'phpType' => 'string',
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'precision' => 5,
                        'scale' => 2,
                        'defaultValue' => '33.22',
                    ],
                    'time' => [
                        'type' => 'timestamp',
                        'dbType' => 'timestamp',
                        'phpType' => 'string',
                        'allowNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'precision' => null,
                        'scale' => null,
                        'defaultValue' => '2002-01-01 00:00:00',
                    ],
                    'bool_col' => [
                        'type' => 'boolean',
                        'dbType' => 'bool',
                        'phpType' => 'boolean',
                        'allowNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'precision' => null,
                        'scale' => null,
                        'defaultValue' => null,
                    ],
                    'bool_col2' => [
                        'type' => 'boolean',
                        'dbType' => 'bool',
                        'phpType' => 'boolean',
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'precision' => null,
                        'scale' => null,
                        'defaultValue' => true,
                    ],
                    'ts_default' => [
                        'type' => 'timestamp',
                        'dbType' => 'timestamp',
                        'phpType' => 'string',
                        'allowNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'precision' => null,
                        'scale' => null,
                        'defaultValue' => new Expression('now()'),
                    ],
                    'bit_col' => [
                        'type' => 'integer',
                        'dbType' => 'bit',
                        'phpType' => 'integer',
                        'allowNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 8,
                        'precision' => null,
                        'scale' => null,
                        'defaultValue' => 130, //b '10000010'
                    ],
                    'bigint_col' => [
                        'type' => 'bigint',
                        'dbType' => 'int8',
                        'phpType' => 'integer',
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'precision' => 64,
                        'scale' => 0,
                        'defaultValue' => null,
                    ],
                    'intarray_col' => [
                        'type' => 'integer',
                        'dbType' => 'int4',
                        'phpType' => 'integer',
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'precision' => null,
                        'scale' => null,
                        'defaultValue' => null,
                        'dimension' => 1,
                    ],
                    'textarray2_col' => [
                        'type' => 'text',
                        'dbType' => 'text',
                        'phpType' => 'string',
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'precision' => null,
                        'scale' => null,
                        'defaultValue' => null,
                        'dimension' => 2,
                    ],
                    'json_col' => [
                        'type' => 'json',
                        'dbType' => 'json',
                        'phpType' => 'array',
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'precision' => null,
                        'scale' => null,
                        'defaultValue' => ['a' => 1],
                        'dimension' => 0,
                    ],
                    'jsonb_col' => [
                        'type' => 'json',
                        'dbType' => 'jsonb',
                        'phpType' => 'array',
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'precision' => null,
                        'scale' => null,
                        'defaultValue' => null,
                        'dimension' => 0,
                    ],
                    'jsonarray_col' => [
                        'type' => 'json',
                        'dbType' => 'json',
                        'phpType' => 'array',
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'precision' => null,
                        'scale' => null,
                        'defaultValue' => null,
                        'dimension' => 1,
                    ],
                ],
            ],
        ];
    }

    public function columnsTypeChar(): array
    {
        $columnsTypeChar = parent::columnsTypeChar();

        $columnsTypeChar[0][3] = 'bpchar';
        $columnsTypeChar[1][3] = 'varchar';

        return $columnsTypeChar;
    }

    public function constraints(): array
    {
        $constraints = parent::constraints();

        $constraints['1: check'][2][0]->expression('CHECK ((("C_check")::text <> \'\'::text))');
        $constraints['3: foreign key'][2][0]->foreignSchemaName('public');
        $constraints['3: index'][2] = [];

        return $constraints;
    }

    public function tableSchemaCacheWithTablePrefixes(): array
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

    public function tableSchemaWithDbSchemes(): array
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
