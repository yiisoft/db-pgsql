<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests\Provider;

use PDO;
use Yiisoft\Db\Command\Param;
use Yiisoft\Db\Constant\PhpType;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\JsonExpression;
use Yiisoft\Db\Pgsql\Column\BigIntColumnSchema;
use Yiisoft\Db\Pgsql\Column\BinaryColumnSchema;
use Yiisoft\Db\Pgsql\Column\BitColumnSchema;
use Yiisoft\Db\Pgsql\Column\BooleanColumnSchema;
use Yiisoft\Db\Pgsql\Column\IntegerColumnSchema;
use Yiisoft\Db\Pgsql\Schema;
use Yiisoft\Db\Pgsql\StructuredExpression;
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
        $values['bit'][0] = BitColumnSchema::class;

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
        $bigInt = PHP_INT_SIZE === 8 ? 9223372036854775807 : '9223372036854775807';

        return [
            // [dbType, type, phpType, values]
            [
                'int4',
                SchemaInterface::TYPE_INTEGER,
                PhpType::INT,
                [
                    // [dimension, expected, typecast value]
                    [1, [1, 2, 3, null], [1, 2.0, '3', null]],
                    [2, [[1, 2], [3], null], [[1, 2.0], ['3'], null]],
                    [2, [null, null], [null, null]],
                ],
            ],
            [
                'int8',
                SchemaInterface::TYPE_BIGINT,
                PhpType::INT,
                [
                    [1, [1, 2, 3, $bigInt], [1, 2.0, '3', '9223372036854775807']],
                    [2, [[1, 2], [3], [$bigInt]], [[1, 2.0], ['3'], ['9223372036854775807']]],
                ],
            ],
            [
                'float8',
                SchemaInterface::TYPE_DOUBLE,
                PhpType::FLOAT,
                [
                    [1, [1.0, 2.2, 3.3, null], [1, 2.2, '3.3', null]],
                    [2, [[1.0, 2.2], [3.3, null]], [[1, 2.2], ['3.3', null]]],
                ],
            ],
            [
                'bool',
                SchemaInterface::TYPE_BOOLEAN,
                PhpType::BOOL,
                [
                    [1, [true, true, true, false, false, false, null], [true, 1, '1', false, 0, '0', null]],
                    [2, [[true, true, true, false, false, false, null]], [[true, 1, '1', false, 0, '0', null]]],
                ],
            ],
            [
                'varchar',
                SchemaInterface::TYPE_STRING,
                PhpType::STRING,
                [
                    [1, ['1', '2', '1', '0', '', null], [1, '2', true, false, '', null]],
                    [2, [['1', '2', '1', '0'], [''], [null]], [[1, '2', true, false], [''], [null]]],
                ],
            ],
            [
                'bytea',
                SchemaInterface::TYPE_BINARY,
                PhpType::MIXED,
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
                'jsonb',
                SchemaInterface::TYPE_JSON,
                PhpType::MIXED,
                [
                    [1, [
                        new JsonExpression([1, 2, 3], 'jsonb'),
                        new JsonExpression(['key' => 'value'], 'jsonb'),
                        new JsonExpression(['key' => 'value']),
                        null,
                    ], [[1, 2, 3], ['key' => 'value'], new JsonExpression(['key' => 'value']), null]],
                    [2, [
                        [
                            new JsonExpression([1, 2, 3], 'jsonb'),
                            new JsonExpression(['key' => 'value'], 'jsonb'),
                            new JsonExpression(['key' => 'value']),
                            null,
                        ],
                        null,
                    ], [[[1, 2, 3], ['key' => 'value'], new JsonExpression(['key' => 'value']), null], null]],
                ],
            ],
            [
                'varbit',
                Schema::TYPE_BIT,
                PhpType::INT,
                [
                    [1, ['1011', '1001', null], [0b1011, '1001', null]],
                    [2, [['1011', '1001', null]], [[0b1011, '1001', null]]],
                ],
            ],
            [
                'price_composite',
                Schema::TYPE_STRUCTURED,
                PhpType::ARRAY,
                [
                    [
                        1,
                        [
                            new StructuredExpression(['value' => 10, 'currency' => 'USD'], 'price_composite'),
                            null,
                        ],
                        [
                            ['value' => 10, 'currency' => 'USD'],
                            null,
                        ],
                    ],
                    [
                        2,
                        [[
                            new StructuredExpression(['value' => 10, 'currency' => 'USD'], 'price_composite'),
                            null,
                        ]],
                        [[
                            ['value' => 10, 'currency' => 'USD'],
                            null,
                        ]],
                    ],
                ],
            ],
        ];
    }

    public static function phpTypecastArrayColumns()
    {
        $bigInt = PHP_INT_SIZE === 8 ? 9223372036854775807 : '9223372036854775807';

        return [
            // [dbtype, type, phpType, values]
            [
                'int4',
                SchemaInterface::TYPE_INTEGER,
                PhpType::INT,
                [
                    // [dimension, expected, typecast value]
                    [1, [1, 2, 3, null], '{1,2,3,}'],
                    [2, [[1, 2], [3], null], '{{1,2},{3},}'],
                ],
            ],
            [
                'int8',
                SchemaInterface::TYPE_BIGINT,
                PhpType::INT,
                [
                    [1, [1, 2, $bigInt], '{1,2,9223372036854775807}'],
                    [2, [[1, 2], [$bigInt]], '{{1,2},{9223372036854775807}}'],
                ],
            ],
            [
                'float8',
                SchemaInterface::TYPE_DOUBLE,
                PhpType::FLOAT,
                [
                    [1, [1.0, 2.2, null], '{1,2.2,}'],
                    [2, [[1.0], [2.2, null]], '{{1},{2.2,}}'],
                ],
            ],
            [
                'bool',
                SchemaInterface::TYPE_BOOLEAN,
                PhpType::BOOL,
                [
                    [1, [true, false, null], '{t,f,}'],
                    [2, [[true, false, null]], '{{t,f,}}'],
                ],
            ],
            [
                'varchar',
                SchemaInterface::TYPE_STRING,
                PhpType::STRING,
                [
                    [1, ['1', '2', '', null], '{1,2,"",}'],
                    [2, [['1', '2'], [''], [null]], '{{1,2},{""},{NULL}}'],
                ],
            ],
            [
                'bytea',
                SchemaInterface::TYPE_BINARY,
                PhpType::MIXED,
                [
                    [1, ["\x10\x11", '', null], '{\x1011,"",}'],
                    [2, [["\x10\x11"], ['', null]], '{{\x1011},{"",}}'],
                ],
            ],
            [
                'jsonb',
                SchemaInterface::TYPE_JSON,
                PhpType::MIXED,
                [
                    [1, [[1, 2, 3], null], '{"[1,2,3]",}'],
                    [1, [[1, 2, 3]], '{{1,2,3}}'],
                    [2, [[[1, 2, 3, null], null]], '{{"[1,2,3,null]",}}'],
                ],
            ],
            [
                'varbit',
                Schema::TYPE_BIT,
                PhpType::INT,
                [
                    [1, [0b1011, 0b1001, null], '{1011,1001,}'],
                    [2, [[0b1011, 0b1001, null]], '{{1011,1001,}}'],
                ],
            ],
            [
                'price_structured',
                Schema::TYPE_STRUCTURED,
                PhpType::ARRAY,
                [
                    [1, [['10', 'USD'], null], '{"(10,USD)",}'],
                    [2, [[['10', 'USD'], null]], '{{"(10,USD)",}}'],
                ],
            ],
        ];
    }
}
