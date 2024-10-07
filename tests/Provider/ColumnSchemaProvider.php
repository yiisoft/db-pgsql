<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests\Provider;

use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Pgsql\Column\BigIntColumnSchema;
use Yiisoft\Db\Pgsql\Column\BinaryColumnSchema;
use Yiisoft\Db\Pgsql\Column\BitColumnSchema;
use Yiisoft\Db\Pgsql\Column\BooleanColumnSchema;
use Yiisoft\Db\Pgsql\Column\ColumnBuilder;
use Yiisoft\Db\Pgsql\Column\IntegerColumnSchema;

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

    public static function phpTypecastArrayColumns()
    {
        return [
            // [column, values]
            [
                ColumnBuilder::integer(),
                [
                    // [dimension, expected, typecast value]
                    [1, [1, 2, 3, null], '{1,2,3,}'],
                    [2, [[1, 2], [3], null], '{{1,2},{3},}'],
                ],
            ],
            [
                new BigIntColumnSchema(),
                [
                    [1, ['1', '2', '9223372036854775807'], '{1,2,9223372036854775807}'],
                    [2, [['1', '2'], ['9223372036854775807']], '{{1,2},{9223372036854775807}}'],
                ],
            ],
            [
                ColumnBuilder::double(),
                [
                    [1, [1.0, 2.2, null], '{1,2.2,}'],
                    [2, [[1.0], [2.2, null]], '{{1},{2.2,}}'],
                ],
            ],
            [
                ColumnBuilder::boolean(),
                [
                    [1, [true, false, null], '{t,f,}'],
                    [2, [[true, false, null]], '{{t,f,}}'],
                ],
            ],
            [
                ColumnBuilder::string(),
                [
                    [1, ['1', '2', '', null], '{1,2,"",}'],
                    [2, [['1', '2'], [''], [null]], '{{1,2},{""},{NULL}}'],
                ],
            ],
            [
                ColumnBuilder::binary(),
                [
                    [1, ["\x10\x11", '', null], '{\x1011,"",}'],
                    [2, [["\x10\x11"], ['', null]], '{{\x1011},{"",}}'],
                ],
            ],
            [
                ColumnBuilder::json(),
                [
                    [1, [[1, 2, 3], null], '{"[1,2,3]",}'],
                    [1, [[1, 2, 3]], '{{1,2,3}}'],
                    [2, [[[1, 2, 3, null], null]], '{{"[1,2,3,null]",}}'],
                ],
            ],
            [
                ColumnBuilder::bit(),
                [
                    [1, [0b1011, 0b1001, null], '{1011,1001,}'],
                    [2, [[0b1011, 0b1001, null]], '{{1011,1001,}}'],
                ],
            ],
            [
                ColumnBuilder::structured(),
                [
                    [1, [['10', 'USD'], null], '{"(10,USD)",}'],
                    [2, [[['10', 'USD'], null]], '{{"(10,USD)",}}'],
                ],
            ],
        ];
    }
}
