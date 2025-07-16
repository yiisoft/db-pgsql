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
            . ' VALUES (1, :qp0, 0, TRUE, :qp1::json), (2, :qp2, -1, FALSE, :qp3)';

        $batchInsert['binds params from jsonExpression'] = [
            '{{%type}}',
            [
                [
                    new JsonExpression(
                        ['username' => 'silverfire', 'is_active' => true, 'langs' => ['Ukrainian', 'Russian', 'English']]
                    ),
                    1,
                    1.0,
                    '',
                    false,
                ],
            ],
            ['json_col', 'int_col', 'float_col', 'char_col', 'bool_col'],
            'expected' => <<<SQL
            INSERT INTO "type" ("json_col", "int_col", "float_col", "char_col", "bool_col") VALUES (:qp0, 1, 1, :qp1, FALSE)
            SQL,
            'expectedParams' => [
                ':qp0' => '{"username":"silverfire","is_active":true,"langs":["Ukrainian","Russian","English"]}',
                ':qp1' => '',
            ],
        ];

        $batchInsert['binds params from arrayExpression'] = [
            '{{%type}}',
            [[new ArrayExpression([1, null, 3], 'int'), 1, 1.0, '', false]],
            ['intarray_col', 'int_col', 'float_col', 'char_col', 'bool_col'],
            'expected' => <<<SQL
            INSERT INTO "type" ("intarray_col", "int_col", "float_col", "char_col", "bool_col") VALUES (ARRAY[1,NULL,3]::int[], 1, 1, :qp0, FALSE)
            SQL,
            'expectedParams' => [':qp0' => ''],
        ];

        $batchInsert['casts string to int according to the table schema'] = [
            '{{%type}}',
            [['3', '1.1', '', false]],
            ['int_col', 'float_col', 'char_col', 'bool_col'],
            'expected' => <<<SQL
            INSERT INTO "type" ("int_col", "float_col", "char_col", "bool_col") VALUES (3, 1.1, :qp0, FALSE)
            SQL,
            'expectedParams' => [':qp0' => ''],
        ];

        $batchInsert['binds params from jsonbExpression'] = [
            '{{%type}}',
            [[new JsonExpression(['a' => true]), 1, 1.1, '', false]],
            ['jsonb_col', 'int_col', 'float_col', 'char_col', 'bool_col'],
            'expected' => <<<SQL
            INSERT INTO "type" ("jsonb_col", "int_col", "float_col", "char_col", "bool_col") VALUES (:qp0, 1, 1.1, :qp1, FALSE)
            SQL,
            'expectedParams' => [':qp0' => '{"a":true}', ':qp1' => ''],
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
