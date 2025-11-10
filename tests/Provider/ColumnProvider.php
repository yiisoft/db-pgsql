<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests\Provider;

use DateTimeImmutable;
use DateTimeZone;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Pgsql\Column\ArrayColumn;
use Yiisoft\Db\Pgsql\Column\ArrayLazyColumn;
use Yiisoft\Db\Pgsql\Column\BigBitColumn;
use Yiisoft\Db\Pgsql\Column\BigIntColumn;
use Yiisoft\Db\Pgsql\Column\BinaryColumn;
use Yiisoft\Db\Pgsql\Column\BitColumn;
use Yiisoft\Db\Pgsql\Column\BooleanColumn;
use Yiisoft\Db\Pgsql\Column\IntegerColumn;
use Yiisoft\Db\Pgsql\Column\StructuredColumn;
use Yiisoft\Db\Pgsql\Column\StructuredLazyColumn;
use Yiisoft\Db\Pgsql\Data\LazyArray;
use Yiisoft\Db\Pgsql\Data\StructuredLazyArray;
use Yiisoft\Db\Schema\Column\DateTimeColumn;
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
            new BitColumn(size: 4),
            [
                [null, null],
                [null, ''],
                ['1001', 0b1001],
                ['1001', '1001'],
                ['0001', 1.0],
                ['0001', true],
                ['0000', false],
                [$expression = new Expression('1001'), $expression],
            ],
        ];
        $values['bigbit'] = [
            new BigBitColumn(size: 64),
            [
                [null, null],
                [null, ''],
                ['0000000000000000000000000000000000000000000000000000000000001001', 0b1001],
                ['0000000000000000000000000000000000000000000000000000000000000001', 1.0],
                ['0000000000000000000000000000000000000000000000000000000000000001', true],
                ['0000000000000000000000000000000000000000000000000000000000000000', false],
                ['1100000100011100100110001011000010100000001011001101111011100000', '1100000100011100100110001011000010100000001011001101111011100000'],
                ['1001', '1001'],
                ['13915164833036950442', '13915164833036950442'],
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
            'bigbit' => [
                new BigBitColumn(size: 64),
                [
                    [null, null],
                    ['1001', '1001'],
                    ['1100000100011100100110001011000010100000001011001101111011100000', '1100000100011100100110001011000010100000001011001101111011100000'],
                ],
            ],
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
        $utcTimezone = new DateTimeZone('UTC');

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
                new DateTimeColumn(),
                [
                    [
                        1,
                        [
                            new DateTimeImmutable('2025-04-19 14:11:35', $utcTimezone),
                            new DateTimeImmutable('2025-04-19 00:00:00', $utcTimezone),
                            null,
                        ],
                        '{2025-04-19 14:11:35,2025-04-19 00:00:00,}',
                    ],
                    [
                        2,
                        [
                            [new DateTimeImmutable('2025-04-19 14:11:35', $utcTimezone)],
                            [new DateTimeImmutable('2025-04-19 00:00:00', $utcTimezone), null],
                        ],
                        '{{2025-04-19 14:11:35},{2025-04-19 00:00:00,}}',
                    ],
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
