<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests\Provider;

use PDO;
use Yiisoft\Db\Command\Param;
use Yiisoft\Db\Expression\ArrayExpression;
use Yiisoft\Db\Expression\JsonExpression;
use Yiisoft\Db\Pgsql\Schema;
use Yiisoft\Db\Schema\SchemaInterface;

use function fopen;

class ColumnSchemaProvider
{
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
