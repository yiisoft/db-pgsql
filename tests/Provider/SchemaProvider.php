<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests\Provider;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Pgsql\Column\ArrayColumn;
use Yiisoft\Db\Pgsql\Column\BinaryColumn;
use Yiisoft\Db\Pgsql\Column\BitColumn;
use Yiisoft\Db\Pgsql\Column\BooleanColumn;
use Yiisoft\Db\Pgsql\Column\IntegerColumn;
use Yiisoft\Db\Schema\Column\DoubleColumn;
use Yiisoft\Db\Schema\Column\JsonColumn;
use Yiisoft\Db\Schema\Column\StringColumn;

final class SchemaProvider extends \Yiisoft\Db\Tests\Provider\SchemaProvider
{
    public static function columns(): array
    {
        return [
            [
                [
                    'int_col' => new IntegerColumn(
                        dbType: 'int4',
                        notNull: true,
                        scale: 0,
                    ),
                    'int_col2' => new IntegerColumn(
                        dbType: 'int4',
                        scale: 0,
                        defaultValue: 1,
                    ),
                    'tinyint_col' => new IntegerColumn(
                        ColumnType::SMALLINT,
                        dbType: 'int2',
                        scale: 0,
                        defaultValue: 1,
                    ),
                    'smallint_col' => new IntegerColumn(
                        ColumnType::SMALLINT,
                        dbType: 'int2',
                        scale: 0,
                        defaultValue: 1,
                    ),
                    'char_col' => new StringColumn(
                        ColumnType::CHAR,
                        dbType: 'bpchar',
                        notNull: true,
                        size: 100,
                    ),
                    'char_col2' => new StringColumn(
                        dbType: 'varchar',
                        size: 100,
                        defaultValue: 'some\'thing',
                    ),
                    'char_col3' => new StringColumn(
                        ColumnType::TEXT,
                        dbType: 'text',
                    ),
                    'char_col4' => new StringColumn(
                        dbType: 'varchar',
                        defaultValue: "first line\nsecond line",
                    ),
                    'float_col' => new DoubleColumn(
                        dbType: 'float8',
                        notNull: true,
                    ),
                    'float_col2' => new DoubleColumn(
                        dbType: 'float8',
                        defaultValue: 1.23,
                    ),
                    'blob_col' => new BinaryColumn(
                        dbType: 'bytea',
                        defaultValue: 'a binary value',
                    ),
                    'numeric_col' => new DoubleColumn(
                        ColumnType::DECIMAL,
                        dbType: 'numeric',
                        size: 5,
                        scale: 2,
                        defaultValue: 33.22,
                    ),
                    'time' => new StringColumn(
                        ColumnType::TIMESTAMP,
                        dbType: 'timestamp',
                        notNull: true,
                        size: 6,
                        defaultValue: '2002-01-01 00:00:00',
                    ),
                    'bool_col' => new BooleanColumn(
                        dbType: 'bool',
                        notNull: true,
                    ),
                    'bool_col2' => new BooleanColumn(
                        dbType: 'bool',
                        defaultValue: true,
                    ),
                    'ts_default' => new StringColumn(
                        ColumnType::TIMESTAMP,
                        dbType: 'timestamp',
                        notNull: true,
                        size: 6,
                        defaultValue: new Expression('now()'),
                    ),
                    'bit_col' => new BitColumn(
                        dbType: 'bit',
                        notNull: true,
                        size: 8,
                        defaultValue: 0b1000_0010, // 130
                    ),
                    'varbit_col' => new BitColumn(
                        dbType: 'varbit',
                        notNull: true,
                        defaultValue: 0b100, // 4
                    ),
                    'bigint_col' => new IntegerColumn(
                        ColumnType::BIGINT,
                        dbType: 'int8',
                        scale: 0,
                    ),
                    'intarray_col' => new ArrayColumn(
                        dbType: 'int4',
                        scale: 0,
                        dimension: 1,
                        column: new IntegerColumn(
                            dbType: 'int4',
                            name: 'intarray_col',
                            notNull: false,
                            scale: 0,
                        ),
                    ),
                    'numericarray_col' => new ArrayColumn(
                        dbType: 'numeric',
                        size: 5,
                        scale: 2,
                        dimension: 1,
                        column: new DoubleColumn(
                            ColumnType::DECIMAL,
                            dbType: 'numeric',
                            name: 'numericarray_col',
                            notNull: false,
                            size: 5,
                            scale: 2,
                        ),
                    ),
                    'varchararray_col' => new ArrayColumn(
                        dbType: 'varchar',
                        size: 100,
                        dimension: 1,
                        column: new StringColumn(
                            dbType: 'varchar',
                            name: 'varchararray_col',
                            notNull: false,
                            size: 100,
                        ),
                    ),
                    'textarray2_col' => new ArrayColumn(
                        dbType: 'text',
                        dimension: 2,
                        column: new StringColumn(
                            ColumnType::TEXT,
                            dbType: 'text',
                            name: 'textarray2_col',
                            notNull: false,
                        ),
                    ),
                    'json_col' => new JsonColumn(
                        dbType: 'json',
                        defaultValue: ['a' => 1],
                    ),
                    'jsonb_col' => new JsonColumn(
                        dbType: 'jsonb',
                    ),
                    'jsonarray_col' => new ArrayColumn(
                        dbType: 'json',
                        dimension: 1,
                        column: new JsonColumn(
                            dbType: 'json',
                            name: 'jsonarray_col',
                            notNull: false,
                        ),
                    ),
                ],
                'tableName' => 'type',
            ],
            [
                [
                    'id' => new IntegerColumn(
                        dbType: 'int4',
                        primaryKey: true,
                        notNull: true,
                        autoIncrement: true,
                        sequenceName: 'animal_id_seq',
                        scale: 0,
                    ),
                    'type' => new StringColumn(
                        dbType: 'varchar',
                        notNull: true,
                        size: 255,
                    ),
                ],
                'animal',
            ],
            [
                [
                    'uuid' => new StringColumn(
                        dbType: 'uuid',
                        primaryKey: true,
                        notNull: true,
                    ),
                    'col' => new StringColumn(
                        dbType: 'varchar',
                        size: 16,
                    ),
                ],
                'table_uuid',
            ],
            [
                [
                    'C_id' => new IntegerColumn(
                        dbType: 'int4',
                        primaryKey: true,
                        notNull: true,
                        scale: 0,
                    ),
                    'C_not_null' => new IntegerColumn(
                        dbType: 'int4',
                        notNull: true,
                        scale: 0,
                    ),
                    'C_check' => new StringColumn(
                        dbType: 'varchar',
                        size: 255,
                    ),
                    'C_unique' => new IntegerColumn(
                        dbType: 'int4',
                        notNull: true,
                        scale: 0,
                        unique: true,
                    ),
                    'C_default' => new IntegerColumn(
                        dbType: 'int4',
                        notNull: true,
                        scale: 0,
                        defaultValue: 0,
                    ),
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
