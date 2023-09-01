<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests\Provider;

use PDO;
use Yiisoft\Db\Command\Param;
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

class ColumnSchemaProvider extends \Yiisoft\Db\Tests\Provider\ColumnSchemaProvider
{
    public static function predefinedTypes(): array
    {
        $values = parent::predefinedTypes();
        $values['integer'][0] = IntegerColumnSchema::class;
        $values['bigint'][0] = BigIntColumnSchema::class;
        $values['binary'][0] = BinaryColumnSchema::class;
        $values['boolean'][0] = BooleanColumnSchema::class;
        $values['bit'] = [BitColumnSchema::class, Schema::TYPE_BIT, SchemaInterface::PHP_TYPE_INTEGER];

        return $values;
    }

    public static function dbTypecastColumns(): array
    {
        $values = parent::dbTypecastColumns();
        $values['integer'][0] = IntegerColumnSchema::class;
        $values['bigint'][0] = BigIntColumnSchema::class;
        $values['binary'][0] = BinaryColumnSchema::class;
        $values['boolean'][0] = BooleanColumnSchema::class;
        $values['bit'] = [
            BitColumnSchema::class,
            [
                [null, null],
                [null, ''],
                ['1001', 0b1001],
                ['1001', '1001'],
                ['1', 1.0],
                ['1', true],
                ['0', false],
                [$expression = new Expression('1001'), $expression],
            ],
        ];

        return $values;
    }

    public static function phpTypecastColumns(): array
    {
        $values = parent::phpTypecastColumns();
        $values['integer'][0] = IntegerColumnSchema::class;
        $values['bigint'][0] = BigIntColumnSchema::class;
        $values['binary'][0] = BinaryColumnSchema::class;
        $values['binary'][1][] = ["\x10\x11\x12", '\x101112'];
        $values['boolean'] = [
            BooleanColumnSchema::class,
            [
                [null, null],
                [true, true],
                [true, '1'],
                [true, 't'],
                [false, false],
                [false, '0'],
                [false, 'f'],
            ],
        ];
        $values['bit'] = [
            BitColumnSchema::class,
            [
                [null, null],
                [0b1001, '1001'],
                [0b1001, 0b1001],
            ],
        ];

        return $values;
    }

    public static function dbTypecastArrayColumns()
    {
        return [
            // [type, phpType, values]
            [
                SchemaInterface::TYPE_INTEGER,
                SchemaInterface::PHP_TYPE_INTEGER,
                [
                    // dimension, expected, typecast value
                    [1, [1, 2, 3, null], [1, 2.0, '3', null]],
                    [2, [[1, 2, 3, null]], [[1, 2.0, '3', null]]],
                ],
            ],
            [
                SchemaInterface::TYPE_DOUBLE,
                SchemaInterface::PHP_TYPE_DOUBLE,
                [
                    [1, [1.0, 2.2, 3.3, null], [1, 2.2, '3.3', null]],
                    [2, [[1.0, 2.2, 3.3, null]], [[1, 2.2, '3.3', null]]],
                ],
            ],
            [
                SchemaInterface::TYPE_BOOLEAN,
                SchemaInterface::PHP_TYPE_BOOLEAN,
                [
                    [1, [true, true, true, false, false, false, null], [true, 1, '1', false, 0, '0', null]],
                    [2, [[true, true, true, false, false, false, null]], [[true, 1, '1', false, 0, '0', null]]],
                ],
            ],
            [
                SchemaInterface::TYPE_STRING,
                SchemaInterface::PHP_TYPE_STRING,
                [
                    [1, ['1', '2', '1', '0', '', null], [1, '2', true, false, '', null]],
                    [2, [['1', '2', '1', '0', '', null]], [[1, '2', true, false, '', null]]],
                ],
            ],
            [
                SchemaInterface::TYPE_BINARY,
                SchemaInterface::PHP_TYPE_RESOURCE,
                [
                    [1, [
                        '1',
                        new Param("\x10", PDO::PARAM_LOB),
                        $resource = fopen('php://memory', 'rb'),
                        null,
                    ], [1, "\x10", $resource, null]],
                    [2, [[
                        '1',
                        new Param("\x10", PDO::PARAM_LOB),
                        $resource = fopen('php://memory', 'rb'),
                        null,
                    ]], [[1, "\x10", $resource, null]]],
                ],
            ],
            [
                SchemaInterface::TYPE_JSON,
                SchemaInterface::PHP_TYPE_ARRAY,
                [
                    [1, [
                        new JsonExpression([1, 2, 3], 'json'),
                        new JsonExpression(['key' => 'value'], 'json'),
                        new JsonExpression(['key' => 'value']),
                        null,
                    ], [[1, 2, 3], ['key' => 'value'], new JsonExpression(['key' => 'value']), null]],
                    [2, [[
                        new JsonExpression([1, 2, 3], 'json'),
                        new JsonExpression(['key' => 'value'], 'json'),
                        new JsonExpression(['key' => 'value']),
                        null,
                    ]], [[[1, 2, 3], ['key' => 'value'], new JsonExpression(['key' => 'value']), null]]],
                ],
            ],
            [
                Schema::TYPE_BIT,
                SchemaInterface::PHP_TYPE_INTEGER,
                [
                    [1, ['1011', '1001', null], [0b1011, '1001', null]],
                    [2, [['1011', '1001', null]], [[0b1011, '1001', null]]],
                ],
            ],
        ];
    }

    public static function phpTypecastArrayColumns()
    {
        return [
            // [type, phpType, values]
            [
                SchemaInterface::TYPE_INTEGER,
                SchemaInterface::PHP_TYPE_INTEGER,
                [
                    // [dimension, expected, typecast value]
                    [1, [1, 2, 3, null], '{1,2,3,}'],
                    [2, [[1, 2, 3, null]], '{{1,2,3,}}'],
                ],
            ],
            [
                SchemaInterface::TYPE_DOUBLE,
                SchemaInterface::PHP_TYPE_DOUBLE,
                [
                    [1, [1.0, 2.2, null], '{1,2.2,}'],
                    [2, [[1.0, 2.2, null]], '{{1,2.2,}}'],
                ],
            ],
            [
                SchemaInterface::TYPE_BOOLEAN,
                SchemaInterface::PHP_TYPE_BOOLEAN,
                [
                    [1, [true, false, null], '{t,f,}'],
                    [2, [[true, false, null]], '{{t,f,}}'],
                ],
            ],
            [
                SchemaInterface::TYPE_STRING,
                SchemaInterface::PHP_TYPE_STRING,
                [
                    [1, ['1', '2', '', null], '{1,2,"",}'],
                    [2, [['1', '2', '', null]], '{{1,2,"",}}'],
                ],
            ],
            [
                SchemaInterface::TYPE_BINARY,
                SchemaInterface::PHP_TYPE_RESOURCE,
                [
                    [1, ["\x10\x11", '', null], '{\x1011,"",}'],
                    [2, [["\x10\x11", '', null]], '{{\x1011,"",}}'],
                ],
            ],
            [
                SchemaInterface::TYPE_JSON,
                SchemaInterface::PHP_TYPE_ARRAY,
                [
                    [1, [[1, 2, 3], null], '{"[1,2,3]",}'],
                    [1, [[1, 2, 3]], '{{1,2,3}}'],
                    [2, [[[1, 2, 3], null]], '{{"[1,2,3]",}}'],
                ],
            ],
            [
                Schema::TYPE_BIT,
                SchemaInterface::PHP_TYPE_INTEGER,
                [
                    [1, [0b1011, 0b1001, null], '{1011,1001,}'],
                    [2, [[0b1011, 0b1001, null]], '{{1011,1001,}}'],
                ],
            ],
        ];
    }
}
