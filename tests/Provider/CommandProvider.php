<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests\Provider;

use Yiisoft\Db\Expression\ArrayExpression;
use Yiisoft\Db\Expression\JsonExpression;
use Yiisoft\Db\Pgsql\Column\ColumnBuilder;
use Yiisoft\Db\Pgsql\IndexMethod;
use Yiisoft\Db\Pgsql\Tests\Support\TestTrait;

final class CommandProvider extends \Yiisoft\Db\Tests\Provider\CommandProvider
{
    use TestTrait;

    protected static string $driverName = 'pgsql';

    public static function batchInsert(): array
    {
        $batchInsert = parent::batchInsert();

        $batchInsert['binds json params']['expected'] =
            'INSERT INTO "type" ("int_col", "char_col", "float_col", "bool_col", "json_col")'
            . ' VALUES (:qp0, :qp1, :qp2, :qp3, :qp4::json), (:qp5, :qp6, :qp7, :qp8, :qp9)';

        $batchInsert['binds params from jsonExpression'] = [
            '{{%type}}',
            [
                [
                    new JsonExpression(
                        ['username' => 'silverfire', 'is_active' => true, 'langs' => ['Ukrainian', 'Russian', 'English']]
                    ),
                    1,
                    1,
                    '',
                    false,
                ],
            ],
            ['json_col', 'int_col', 'float_col', 'char_col', 'bool_col'],
            'expected' => <<<SQL
            INSERT INTO "type" ("json_col", "int_col", "float_col", "char_col", "bool_col") VALUES (:qp0, :qp1, :qp2, :qp3, :qp4)
            SQL,
            'expectedParams' => [
                ':qp0' => '{"username":"silverfire","is_active":true,"langs":["Ukrainian","Russian","English"]}',
                ':qp1' => 1,
                ':qp2' => 1.0,
                ':qp3' => '',
                ':qp4' => false,
            ],
        ];

        $batchInsert['binds params from arrayExpression'] = [
            '{{%type}}',
            [[new ArrayExpression([1, null, 3], 'int'), 1, 1, '', false]],
            ['intarray_col', 'int_col', 'float_col', 'char_col', 'bool_col'],
            'expected' => <<<SQL
            INSERT INTO "type" ("intarray_col", "int_col", "float_col", "char_col", "bool_col") VALUES (ARRAY[:qp0,:qp1,:qp2]::int[], :qp3, :qp4, :qp5, :qp6)
            SQL,
            'expectedParams' => [':qp0' => 1, ':qp1' => null, ':qp2' => 3, ':qp3' => 1, ':qp4' => 1.0, ':qp5' => '', ':qp6' => false],
        ];

        $batchInsert['casts string to int according to the table schema'] = [
            '{{%type}}',
            [['3', '1.1', '', false]],
            ['int_col', 'float_col', 'char_col', 'bool_col'],
            'expected' => <<<SQL
            INSERT INTO "type" ("int_col", "float_col", "char_col", "bool_col") VALUES (:qp0, :qp1, :qp2, :qp3)
            SQL,
            'expectedParams' => [':qp0' => 3, ':qp1' => 1.1, ':qp2' => '', ':qp3' => false],
        ];

        $batchInsert['binds params from jsonbExpression'] = [
            '{{%type}}',
            [[new JsonExpression(['a' => true]), 1, 1.1, '', false]],
            ['jsonb_col', 'int_col', 'float_col', 'char_col', 'bool_col'],
            'expected' => <<<SQL
            INSERT INTO "type" ("jsonb_col", "int_col", "float_col", "char_col", "bool_col") VALUES (:qp0, :qp1, :qp2, :qp3, :qp4)
            SQL,
            'expectedParams' => [':qp0' => '{"a":true}', ':qp1' => 1, ':qp2' => 1.1, ':qp3' => '', ':qp4' => false],
        ];


        return $batchInsert;
    }

    public static function rawSql(): array
    {
        return array_merge(parent::rawSql(), [
            [
                'SELECT * FROM customer WHERE id::integer IN (:in, :out)',
                [':in' => 1, ':out' => 2],
                <<<SQL
                SELECT * FROM customer WHERE id::integer IN (1, 2)
                SQL,
            ],
        ]);
    }

    public static function createIndex(): array
    {
        return [
            ...parent::createIndex(),
            [['col1' => ColumnBuilder::integer()], ['col1'], null, IndexMethod::BTREE],
            [['col1' => ColumnBuilder::integer()], ['col1'], null, IndexMethod::HASH],
            [['col1' => ColumnBuilder::integer()], ['col1'], null, IndexMethod::BRIN],
            [['col1' => ColumnBuilder::array()], ['col1'], null, IndexMethod::GIN],
            [['col1' => 'point'], ['col1'], null, IndexMethod::GIST],
            [['col1' => 'point'], ['col1'], null, IndexMethod::SPGIST],
        ];
    }
}
