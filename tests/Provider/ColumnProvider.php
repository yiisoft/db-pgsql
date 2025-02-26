<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests\Provider;

use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Pgsql\Column\ArrayColumn;
use Yiisoft\Db\Pgsql\Column\ArrayLazyColumn;
use Yiisoft\Db\Pgsql\Column\BigIntColumn;
use Yiisoft\Db\Pgsql\Column\BinaryColumn;
use Yiisoft\Db\Pgsql\Column\BitColumn;
use Yiisoft\Db\Pgsql\Column\BooleanColumn;
use Yiisoft\Db\Pgsql\Column\IntegerColumn;
use Yiisoft\Db\Pgsql\Column\StructuredColumn;
use Yiisoft\Db\Pgsql\Column\StructuredLazyColumn;
use Yiisoft\Db\Pgsql\Data\LazyArray;
use Yiisoft\Db\Pgsql\Data\StructuredLazyArray;
use Yiisoft\Db\Schema\Column\DoubleColumn;
use Yiisoft\Db\Schema\Column\JsonColumn;
use Yiisoft\Db\Schema\Column\StringColumn;

class ColumnProvider extends \Yiisoft\Db\Tests\Provider\ColumnProvider
{
    public static function predefinedTypes(): array
    {
        $values = parent::predefinedTypes();
        $values['integer'][0] = IntegerColumn::class;
        $values['bigint'][0] = BigIntColumn::class;
        $values['binary'][0] = BinaryColumn::class;
        $values['boolean'][0] = BooleanColumn::class;
        $values['bit'][0] = BitColumn::class;

        return $values;
    }

    public static function dbTypecastColumns(): array
    {
        $values = parent::dbTypecastColumns();
        $values['integer'][0] = new IntegerColumn();
        $values['bigint'][0] = new BigIntColumn();
        $values['binary'][0] = new BinaryColumn();
        $values['boolean'][0] = new BooleanColumn();
        $values['bit'] = [
            new BitColumn(),
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
        $values['integer'][0] = new IntegerColumn();
        $values['bigint'][0] = new BigIntColumn();
        $values['binary'][0] = new BinaryColumn();
        $values['binary'][1][] = ["\x10\x11\x12", '\x101112'];
        $values['boolean'] = [
            new BooleanColumn(),
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
            new BitColumn(),
            [
                [null, null],
                [0b1001, '1001'],
                [0b1001, 0b1001],
            ],
        ];

        return [
            ...$values,
            'array' => [
                (new ArrayColumn())->column(new IntegerColumn()),
                [
                    [null, null],
                    [[], '{}'],
                    [[1, 2, 3, null], '{1,2,3,}'],
                ],
            ],
            'arrayLazy' => [
                $column = (new ArrayLazyColumn())->column(new IntegerColumn()),
                [
                    [null, null],
                    [new LazyArray('{}', $column->getColumn()), '{}'],
                    [new LazyArray('{1,2,3,}', $column->getColumn()), '{1,2,3,}'],
                ],
            ],
            'structured' => [
                (new StructuredColumn())->columns(['int' => new IntegerColumn(), 'bool' => new BooleanColumn()]),
                [
                    [null, null],
                    [['int' => null, 'bool' => null], '(,)'],
                    [['int' => 1, 'bool' => true], '(1,t)'],
                ],
            ],
            'structuredLazy' => [
                $structuredCol = (new StructuredLazyColumn())->columns(['int' => new IntegerColumn(), 'bool' => new BooleanColumn()]),
                [
                    [null, null],
                    [new StructuredLazyArray('(,)', $structuredCol->getColumns()), '(,)'],
                    [new StructuredLazyArray('(1,t)', $structuredCol->getColumns()), '(1,t)'],
                ],
            ],
        ];
    }

    public static function phpTypecastArrayColumns()
    {
        return [
            // [column, values]
            [
                new IntegerColumn(),
                [
                    // [dimension, expected, typecast value]
                    [1, [1, 2, 3, null], '{1,2,3,}'],
                    [2, [[1, 2], [3], null], '{{1,2},{3},}'],
                ],
            ],
            [
                new BigIntColumn(),
                [
                    [1, ['1', '2', '9223372036854775807'], '{1,2,9223372036854775807}'],
                    [2, [['1', '2'], ['9223372036854775807']], '{{1,2},{9223372036854775807}}'],
                ],
            ],
            [
                new DoubleColumn(),
                [
                    [1, [1.0, 2.2, null], '{1,2.2,}'],
                    [2, [[1.0], [2.2, null]], '{{1},{2.2,}}'],
                ],
            ],
            [
                new BooleanColumn(),
                [
                    [1, [true, false, null], '{t,f,}'],
                    [2, [[true, false, null]], '{{t,f,}}'],
                ],
            ],
            [
                new StringColumn(),
                [
                    [1, ['1', '2', '', null], '{1,2,"",}'],
                    [2, [['1', '2'], [''], [null]], '{{1,2},{""},{NULL}}'],
                ],
            ],
            [
                new BinaryColumn(),
                [
                    [1, ["\x10\x11", '', null], '{\x1011,"",}'],
                    [2, [["\x10\x11"], ['', null]], '{{\x1011},{"",}}'],
                ],
            ],
            [
                new JsonColumn(),
                [
                    [1, [[1, 2, 3], null], '{"[1,2,3]",}'],
                    [1, [[1, 2, 3]], '{{1,2,3}}'],
                    [2, [[[1, 2, 3, null], null]], '{{"[1,2,3,null]",}}'],
                ],
            ],
            [
                new BitColumn(),
                [
                    [1, [0b1011, 0b1001, null], '{1011,1001,}'],
                    [2, [[0b1011, 0b1001, null]], '{{1011,1001,}}'],
                ],
            ],
            [
                new StructuredColumn(),
                [
                    [1, [['10', 'USD'], null], '{"(10,USD)",}'],
                    [2, [[['10', 'USD'], null]], '{{"(10,USD)",}}'],
                ],
            ],
        ];
    }
}
