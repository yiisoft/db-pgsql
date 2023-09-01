<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests\Provider;

use PDO;
use Yiisoft\Db\Command\Param;
use Yiisoft\Db\Expression\ArrayExpression;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\JsonExpression;
use Yiisoft\Db\Pgsql\Column\BigIntColumnSchema;
use Yiisoft\Db\Pgsql\Column\BinaryColumnSchema;
use Yiisoft\Db\Pgsql\Column\BitColumnSchema;
use Yiisoft\Db\Pgsql\Column\BooleanColumnSchema;
use Yiisoft\Db\Pgsql\Column\IntegerColumnSchema;
use Yiisoft\Db\Pgsql\Schema;
use Yiisoft\Db\Schema\SchemaInterface;

use function fopen;

class ColumnSchemaProvider
{
    public static function dbTypecastColumns(): array
    {
        return [[[
            IntegerColumnSchema::class => [
                // [expected, typecast value]
                [null, null],
                [null, ''],
                [1, 1],
                [1, 1.0],
                [1, '1'],
                [1, true],
                [0, false],
                [$expression = new Expression('1'), $expression],
            ],
            BigIntColumnSchema::class => [
                [null, null],
                [null, ''],
                [1, 1],
                [1, 1.0],
                [1, '1'],
                [1, true],
                [0, false],
                ['12345678901234567890', '12345678901234567890'],
                [$expression = new Expression('1'), $expression],
            ],
            BinaryColumnSchema::class => [
                [null, null],
                ['1', 1],
                ['1', true],
                ['0', false],
                [new Param("\x10\x11\x12", PDO::PARAM_LOB), "\x10\x11\x12"],
                [$resource = fopen('php://memory', 'rb'), $resource],
                [$expression = new Expression('expression'), $expression],
            ],
            BooleanColumnSchema::class => [
                [null, null],
                [null, ''],
                [true, true],
                [true, 1],
                [true, 1.0],
                [true, '1'],
                [false, false],
                [false, 0],
                [false, 0.0],
                [false, '0'],
                [false, false],
                [$expression = new Expression('expression'), $expression],
            ],
            BitColumnSchema::class => [
                [null, null],
                [null, ''],
                ['1001', 0b1001],
                ['1001', '1001'],
                ['1', 1.0],
                ['1', true],
                ['0', false],
                [$expression = new Expression('1001'), $expression],
            ],
        ]]];
    }

    public static function phpTypecastColumns(): array
    {
        return [[[
            IntegerColumnSchema::class => [
                // [expected, typecast value]
                [null, null],
                [1, 1],
                [1, '1'],
            ],
            BigIntColumnSchema::class => [
                [null, null],
                [1, 1],
                [1, '1'],
                ['12345678901234567890', '12345678901234567890'],
            ],
            BinaryColumnSchema::class => [
                [null, null],
                ['', ''],
                ["\x10\x11\x12", "\x10\x11\x12"],
                [$resource = fopen('php://memory', 'rb'), $resource],
            ],
            BooleanColumnSchema::class => [
                [null, null],
                [true, true],
                [true, '1'],
                [true, 't'],
                [false, false],
                [false, '0'],
                [false, 'f'],
            ],
            BitColumnSchema::class => [
                [null, null],
                [0b1001, '1001'],
                [0b1001, 0b1001],
            ],
        ]]];
    }

    public static function dbTypecastArrayColumns()
    {
        return [
            // [type, phpType, expected, typecast value]
            [
                SchemaInterface::TYPE_INTEGER,
                SchemaInterface::PHP_TYPE_INTEGER,
                new ArrayExpression([1, 2, 3, null]),
                [1, 2.0, '3', null],
            ],
            [
                SchemaInterface::TYPE_DOUBLE,
                SchemaInterface::PHP_TYPE_DOUBLE,
                new ArrayExpression([1.0, 2.2, 3.3, null]),
                [1, 2.2, '3.3', null],
            ],
            [
                SchemaInterface::TYPE_BOOLEAN,
                SchemaInterface::PHP_TYPE_BOOLEAN,
                new ArrayExpression([true, true, true, false, false, false, null]),
                [true, 1, '1', false, 0, '0', null],
            ],
            [
                SchemaInterface::TYPE_STRING,
                SchemaInterface::PHP_TYPE_STRING,
                new ArrayExpression(['1', '2', '1', '0', '', null]),
                [1, '2', true, false, '', null],
            ],
            [
                SchemaInterface::TYPE_BINARY,
                SchemaInterface::PHP_TYPE_RESOURCE,
                new ArrayExpression([
                    '1',
                    new Param("\x10", PDO::PARAM_LOB),
                    $resource = fopen('php://memory', 'rb'),
                    null,
                ]),
                [1, "\x10", $resource, null],
            ],
            [
                SchemaInterface::TYPE_JSON,
                SchemaInterface::PHP_TYPE_ARRAY,
                new ArrayExpression([
                    new JsonExpression([1, 2, 3], 'json'),
                    new JsonExpression(['key' => 'value'], 'json'),
                    new JsonExpression(['key' => 'value']),
                    null,
                ]),
                [[1, 2, 3], ['key' => 'value'], new JsonExpression(['key' => 'value']), null],
            ],
            [
                Schema::TYPE_BIT,
                SchemaInterface::PHP_TYPE_INTEGER,
                new ArrayExpression(['1011', '1001', null]),
                [0b1011, '1001', null],
            ],
        ];
    }

    public static function phpTypecastArrayColumns()
    {
        return [
            // [type, phpType, expected, typecast value]
            [
                SchemaInterface::TYPE_INTEGER,
                SchemaInterface::PHP_TYPE_INTEGER,
                [1, 2, 3, null],
                '{1,2,3,}',
            ],
            [
                SchemaInterface::TYPE_DOUBLE,
                SchemaInterface::PHP_TYPE_DOUBLE,
                [1.0, 2.2, null],
                '{1,2.2,}',
            ],
            [
                SchemaInterface::TYPE_BOOLEAN,
                SchemaInterface::PHP_TYPE_BOOLEAN,
                [true, false, null],
                '{t,f,}',
            ],
            [
                SchemaInterface::TYPE_STRING,
                SchemaInterface::PHP_TYPE_STRING,
                ['1', '2', '', null],
                '{1,2,"",}',
            ],
            [
                SchemaInterface::TYPE_BINARY,
                SchemaInterface::PHP_TYPE_RESOURCE,
                ["\x10\x11", '', null],
                '{\x1011,"",}',
            ],
            [
                SchemaInterface::TYPE_JSON,
                SchemaInterface::PHP_TYPE_ARRAY,
                [[1, 2, 3], null],
                '{"[1,2,3]",}',
            ],
            [
                SchemaInterface::TYPE_JSON,
                SchemaInterface::PHP_TYPE_ARRAY,
                [[1, 2, 3]],
                '{{1,2,3}}',
            ],
            [
                Schema::TYPE_BIT,
                SchemaInterface::PHP_TYPE_INTEGER,
                [0b1011, 0b1001, null],
                '{1011,1001,}',
            ],
        ];
    }
}
