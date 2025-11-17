<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests\Provider;

use DateTimeImmutable;
use DateTimeZone;
use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Pgsql\Column\ArrayColumn;
use Yiisoft\Db\Pgsql\Column\BigBitColumn;
use Yiisoft\Db\Pgsql\Column\BinaryColumn;
use Yiisoft\Db\Pgsql\Column\BitColumn;
use Yiisoft\Db\Pgsql\Column\BooleanColumn;
use Yiisoft\Db\Pgsql\Column\DateRangeColumn;
use Yiisoft\Db\Pgsql\Column\Int4RangeColumn;
use Yiisoft\Db\Pgsql\Column\Int8RangeColumn;
use Yiisoft\Db\Pgsql\Column\IntegerColumn;
use Yiisoft\Db\Pgsql\Column\NumRangeColumn;
use Yiisoft\Db\Pgsql\Column\TsRangeColumn;
use Yiisoft\Db\Pgsql\Column\TsTzRangeColumn;
use Yiisoft\Db\Schema\Column\DateTimeColumn;
use Yiisoft\Db\Schema\Column\DoubleColumn;
use Yiisoft\Db\Schema\Column\JsonColumn;
use Yiisoft\Db\Schema\Column\StringColumn;
use Yiisoft\Db\Tests\Support\Assert;

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
                        collation: 'C',
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
                    'timestamp_col' => new DateTimeColumn(
                        dbType: 'timestamp',
                        notNull: true,
                        size: 6,
                        defaultValue: new DateTimeImmutable('2002-01-01 00:00:00', new DateTimeZone('UTC')),
                        shouldConvertTimezone: true,
                    ),
                    'timestamp_default' => new DateTimeColumn(
                        dbType: 'timestamp',
                        notNull: true,
                        size: 6,
                        defaultValue: new Expression('now()'),
                    ),
                    'bool_col' => new BooleanColumn(
                        dbType: 'bool',
                        notNull: true,
                    ),
                    'bool_col2' => new BooleanColumn(
                        dbType: 'bool',
                        defaultValue: true,
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
                    'bigbit_col' => new BigBitColumn(
                        dbType: 'varbit',
                        size: 64,
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
                    'intrange_col' => new Int4RangeColumn(dbType: 'int4range'),
                    'bigintrange_col' => new Int8RangeColumn(dbType: 'int8range'),
                    'numrange_col' => new NumRangeColumn(dbType: 'numrange'),
                    'daterange_col' => new DateRangeColumn(dbType: 'daterange'),
                    'tsrange_col' => new TsRangeColumn(dbType: 'tsrange'),
                    'tstzrange_col' => new TsTzRangeColumn(dbType: 'tstzrange'),
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

        Assert::setPropertyValue($constraints['1: check'][2][0], 'expression', 'CHECK ((("C_check")::text <> \'\'::text))');
        Assert::setPropertyValue($constraints['3: foreign key'][2][0], 'foreignSchemaName', 'public');
        Assert::setPropertyValue($constraints['3: foreign key'][2][0], 'foreignTableName', 'T_constraints_2');
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

    public static function resultColumns(): array
    {
        return [
            [null, []],
            [null, ['native_type' => '']],
            [new IntegerColumn(dbType: 'int4', name: 'int_col'), [
                'pgsql:oid' => 23,
                'pgsql:table_oid' => 40133105,
                'table' => 'type',
                'native_type' => 'int4',
                'pdo_type' => 1,
                'name' => 'int_col',
                'len' => 4,
                'precision' => -1,
            ]],
            [new IntegerColumn(ColumnType::SMALLINT, dbType: 'int2', name: 'smallint_col'), [
                'pgsql:oid' => 21,
                'pgsql:table_oid' => 40133105,
                'table' => 'type',
                'native_type' => 'int2',
                'pdo_type' => 1,
                'name' => 'smallint_col',
                'len' => 2,
                'precision' => -1,
            ]],
            [new StringColumn(ColumnType::CHAR, dbType: 'bpchar', name: 'char_col', size: 100), [
                'pgsql:oid' => 1042,
                'pgsql:table_oid' => 40133105,
                'table' => 'type',
                'native_type' => 'bpchar',
                'pdo_type' => 2,
                'name' => 'char_col',
                'len' => -1,
                'precision' => 104,
            ]],
            [new StringColumn(dbType: 'varchar', name: 'char_col2', size: 100), [
                'pgsql:oid' => 1043,
                'pgsql:table_oid' => 40133105,
                'table' => 'type',
                'native_type' => 'varchar',
                'pdo_type' => 2,
                'name' => 'char_col2',
                'len' => -1,
                'precision' => 104,
            ]],
            [new StringColumn(ColumnType::TEXT, dbType: 'text', name: 'char_col3'), [
                'pgsql:oid' => 25,
                'pgsql:table_oid' => 40133105,
                'table' => 'type',
                'native_type' => 'text',
                'pdo_type' => 2,
                'name' => 'char_col3',
                'len' => -1,
                'precision' => -1,
            ]],
            [new DoubleColumn(dbType: 'float8', name: 'float_col'), [
                'pgsql:oid' => 701,
                'pgsql:table_oid' => 40133105,
                'table' => 'type',
                'native_type' => 'float8',
                'pdo_type' => 2,
                'name' => 'float_col',
                'len' => 8,
                'precision' => -1,
            ]],
            [new BinaryColumn(dbType: 'bytea', name: 'blob_col'), [
                'pgsql:oid' => 17,
                'pgsql:table_oid' => 40133105,
                'table' => 'type',
                'native_type' => 'bytea',
                'pdo_type' => 3,
                'name' => 'blob_col',
                'len' => -1,
                'precision' => -1,
            ]],
            [new DoubleColumn(ColumnType::DECIMAL, dbType: 'numeric', name: 'numeric_col', size: 5, scale: 2), [
                'pgsql:oid' => 1700,
                'pgsql:table_oid' => 40133105,
                'table' => 'type',
                'native_type' => 'numeric',
                'pdo_type' => 2,
                'name' => 'numeric_col',
                'len' => -1,
                'precision' => 327686,
            ]],
            [new DateTimeColumn(dbType: 'timestamp', name: 'time'), [
                'pgsql:oid' => 1114,
                'pgsql:table_oid' => 40133105,
                'table' => 'type',
                'native_type' => 'timestamp',
                'pdo_type' => 2,
                'name' => 'time',
                'len' => 8,
                'precision' => -1,
            ]],
            [new BooleanColumn(dbType: 'bool', name: 'bool_col'), [
                'pgsql:oid' => 16,
                'pgsql:table_oid' => 40133105,
                'table' => 'type',
                'native_type' => 'bool',
                'pdo_type' => 5,
                'name' => 'bool_col',
                'len' => 1,
                'precision' => -1,
            ]],
            [new BitColumn(dbType: 'bit', name: 'bit_col', size: 8), [
                'pgsql:oid' => 1560,
                'pgsql:table_oid' => 40133105,
                'table' => 'type',
                'native_type' => 'bit',
                'pdo_type' => 2,
                'name' => 'bit_col',
                'len' => -1,
                'precision' => 8,
            ]],
            [new BigBitColumn(dbType: 'bit', name: 'bigbit_col', size: 64), [
                'pgsql:oid' => 1560,
                'pgsql:table_oid' => 40133105,
                'table' => 'type',
                'native_type' => 'bit',
                'pdo_type' => 2,
                'name' => 'bigbit_col',
                'len' => -1,
                'precision' => 64,
            ]],
            [new ArrayColumn(dbType: 'int4', name: 'intarray_col', dimension: 1, column: new IntegerColumn(dbType: 'int4', name: 'intarray_col')), [
                'pgsql:oid' => 1007,
                'pgsql:table_oid' => 40133105,
                'table' => 'type',
                'native_type' => '_int4',
                'pdo_type' => 2,
                'name' => 'intarray_col',
                'len' => -1,
                'precision' => -1,
            ]],
            [new JsonColumn(dbType: 'jsonb', name: 'jsonb_col'), [
                'pgsql:oid' => 3802,
                'pgsql:table_oid' => 40133105,
                'table' => 'type',
                'native_type' => 'jsonb',
                'pdo_type' => 2,
                'name' => 'jsonb_col',
                'len' => -1,
                'precision' => -1,
            ]],
            [new StringColumn(ColumnType::TEXT, dbType: 'text', name: 'null'), [
                'pgsql:oid' => 25,
                'pgsql:table_oid' => 0,
                'native_type' => 'text',
                'pdo_type' => 2,
                'name' => 'null',
                'len' => -1,
                'precision' => -1,
            ]],
            [new IntegerColumn(dbType: 'int4', name: '1'), [
                'pgsql:oid' => 23,
                'pgsql:table_oid' => 0,
                'native_type' => 'int4',
                'pdo_type' => 1,
                'name' => '1',
                'len' => 4,
                'precision' => -1,
            ]],
            [new DoubleColumn(ColumnType::DECIMAL, dbType: 'numeric', name: '2.5'), [
                'pgsql:oid' => 1700,
                'pgsql:table_oid' => 0,
                'native_type' => 'numeric',
                'pdo_type' => 2,
                'name' => '2.5',
                'len' => -1,
                'precision' => -1,
            ]],
            [new BooleanColumn(dbType: 'bool', name: 'true'), [
                'pgsql:oid' => 16,
                'pgsql:table_oid' => 0,
                'native_type' => 'bool',
                'pdo_type' => 5,
                'name' => 'true',
                'len' => 1,
                'precision' => -1,
            ]],
            [new DateTimeColumn(ColumnType::DATETIMETZ, dbType: 'timestamptz', name: 'timestamp(3)', size: 3), [
                'pgsql:oid' => 1184,
                'pgsql:table_oid' => 0,
                'native_type' => 'timestamptz',
                'pdo_type' => 2,
                'name' => 'timestamp(3)',
                'len' => 8,
                'precision' => 3,
            ]],
            [new ArrayColumn(dbType: 'int4', name: 'intarray', dimension: 1, column: new IntegerColumn(dbType: 'int4', name: 'intarray')), [
                'pgsql:oid' => 1007,
                'pgsql:table_oid' => 0,
                'native_type' => '_int4',
                'pdo_type' => 2,
                'name' => 'intarray',
                'len' => -1,
                'precision' => -1,
            ]],
            [new JsonColumn(dbType: 'jsonb', name: 'jsonb'), [
                'pgsql:oid' => 3802,
                'pgsql:table_oid' => 0,
                'native_type' => 'jsonb',
                'pdo_type' => 2,
                'name' => 'jsonb',
                'len' => -1,
                'precision' => -1,
            ]],
            [new StringColumn(dbType: 'interval', name: 'interval', size: 3), [
                'pgsql:oid' => 1186,
                'pgsql:table_oid' => 0,
                'native_type' => 'interval',
                'pdo_type' => 2,
                'name' => 'interval',
                'len' => 16,
                'precision' => 2147418115,
            ]],
        ];
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
                    $testConfig['prefix'],
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
