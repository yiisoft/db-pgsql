<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use DateTime;
use DateTimeInterface;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Pgsql\MultiRangeParser;
use Yiisoft\Db\Pgsql\RangeParser;
use const PHP_INT_SIZE;

final class MultiRangeParserTest extends TestCase
{
    public static function emptyDataProvider(): array
    {
        return [
            [
                [],
                '{}',
            ],

            [
                null,
                null,
            ],
        ];
    }

    /**
     * @dataProvider emptyDataProvider
     * @param array|null $expected
     * @param string|null $value
     * @return void
     */
    public function testEmptyMultiRange(?array $expected, ?string $value): void
    {
        $parser = new MultiRangeParser();
        $result = $parser->parse($value);

        $this->assertSame($expected, $result);
    }

    public static function intRangeDataProvider(): array
    {
        return [
            [
                [[3, 6]],
                '{[3,7)}',
            ],

            [
                [[3,6], [8,8]],
                '{[3,7),[8,9)}'
            ],
        ];
    }

    /**
     * @dataProvider intRangeDataProvider
     * @param array $expected
     * @param string $value
     * @return void
     */
    public function testIntMultiRange(array $expected, string $value): void
    {
        $parser = new MultiRangeParser();
        $result = $parser->asMultiInt()->parse($value);

        $this->assertSame($expected, $result);
    }

    public static function dateRangeDataProvider(): array
    {
        return [
            [
                [
                    [
                        new DateTime('2020-12-01'),
                        new DateTime('2021-01-01'),
                    ],

                    [
                        new DateTime('2020-12-01'),
                        new DateTime('2021-01-02'),
                    ],

                    [
                        new DateTime('2021-01-01'),
                        new DateTime('2021-01-02'),
                    ],

                    [
                        null,
                        new DateTime('2021-01-02'),
                    ],

                    [
                        new DateTime('2020-12-01'),
                        null,
                    ],
                ],
                '{[2020-12-01,2021-01-01],[2020-12-01,2021-01-03),(2020-12-31,2021-01-03),(,2021-01-03),[2020-12-01,)}',
            ],
        ];
    }

    /**
     * @dataProvider dateRangeDataProvider
     * @param array $expected
     * @param string $value
     * @return void
     */
    public function testDateMultiRange(array $expected, string $value): void
    {
        $parser = new MultiRangeParser();
        $result = $parser->asMultiDate()->parse($value);

        $this->assertCount(count($expected), $result);

        foreach ($expected as $i => $date) {
            $this->assertCount(2, $result[$i]);

            $lower = $date[0];
            $upper = $date[1];
            $min = $result[$i][0];
            $max = $result[$i][1];

            if ($lower === null) {
                $this->assertNull($min);
            } else {
                $this->assertInstanceOf(DateTimeInterface::class, $min);
                $this->assertSame($lower->format('Y-m-d'), $min->format('Y-m-d'));
            }

            if ($upper === null) {
                $this->assertNull($max);
            } else {
                $this->assertInstanceOf(DateTimeInterface::class, $max);
                $this->assertSame($upper->format('Y-m-d'), $max->format('Y-m-d'));
            }
        }
    }

    public static function numRangeDataProvider(): array
    {
        return [
            [
                [
                    [10.5, 20.7],
                    [null, 39.3],
                    [11.2, null],
                    [null, null],
                ],
                '{[10.5,20.7],(,39.3],(11.2,),[,]}',
            ],
        ];
    }

    /**
     * @dataProvider numRangeDataProvider
     * @param array $expected
     * @param string $value
     * @return void
     */
    public function testNumMultiRange(array $expected, string $value): void
    {
        $parser = new MultiRangeParser();
        $result = $parser->asMultiNumeric()->parse($value);

        $this->assertSame($expected, $result);
    }

    public static function bigIntDataProvider(): array
    {
        return [
            [
                [
                    [
                        PHP_INT_SIZE === 8 ? (int) '2147483648' : (float) '2147483648',
                        PHP_INT_SIZE === 8 ? (int) '2147483649' : (float) '2147483649',
                    ],
                    [
                        null,
                        PHP_INT_SIZE === 8 ? (int) '2147483648' : (float) '2147483648',
                    ],
                ],
                '{[2147483648,2147483649],[,2147483649)}',
            ],
        ];
    }

    /**
     * @dataProvider bigIntDataProvider
     * @param array $expected
     * @param string $value
     * @return void
     */
    public function testBigIntMultiRange(array $expected, string $value): void
    {
        $parser = new MultiRangeParser();
        $result = $parser->asMultiBigInt()->parse($value);

        $this->assertSame($expected, $result);
    }

    public static function timestampDataProvider(): array
    {
        return [
            [
                [
                    [
                        new DateTime('2023-08-07 13:00:00'),
                        new DateTime('2023-08-07 13:30:00'),
                    ],
                    [
                        null,
                        new DateTime('2023-08-07 13:30:00'),
                    ],
                    [
                        new DateTime('2023-08-07 13:00:00'),
                        null,
                    ],
                ],

                '{[2023-08-07 13:00:00,2023-08-07 13:30:00],[,2023-08-07 13:30:00],[2023-08-07 13:00:00,]}'
            ],
        ];
    }

    /**
     * @dataProvider timestampDataProvider
     * @param array $expected
     * @param string $value
     * @return void
     */
    public function testTimestampMultiRange(array $expected, string $value): void
    {
        $parser = new MultiRangeParser();
        $result = $parser->asMultiTimestamp()->parse($value);

        $this->assertCount(count($expected), $result);

        foreach ($expected as $i => $date) {

            $res = $result[$i];

            $this->assertCount(count($date), $res);

            $lower = $res[0];
            $upper = $res[1];
            $min = $date[0];
            $max = $date[1];

            if ($min === null) {
                $this->assertNull($lower);
            } else {
                $this->assertInstanceOf(DateTimeInterface::class, $lower);
                $this->assertSame($min->format('U'), $lower->format('U'));
            }

            if ($max === null) {
                $this->assertNull($upper);
            } else {
                $this->assertInstanceOf(DateTimeInterface::class, $upper);
                $this->assertSame($max->format('U'), $upper->format('U'));
            }
        }
    }

    public static function timestampTzDataProvider(): array
    {
        return [
            [
                [
                    [
                        DateTime::createFromFormat('Y-m-d H:i:sP', '2023-08-07 13:00:00+03'),
                        DateTime::createFromFormat('Y-m-d H:i:sP', '2023-08-07 13:30:00+03'),
                    ],

                    [
                        null,
                        DateTime::createFromFormat('Y-m-d H:i:sP', '2023-08-07 13:30:00+03'),
                    ],

                    [
                        DateTime::createFromFormat('Y-m-d H:i:sP', '2023-08-07 13:00:00+03'),
                        null,
                    ],
                ],

                '{[2023-08-07 13:00:00+03,2023-08-07 13:30:00+03],[,2023-08-07 13:30:00+03],[2023-08-07 13:00:00+03,]}'
            ],
        ];
    }

    /**
     * @dataProvider timestampTzDataProvider
     * @param array $expected
     * @param string $value
     * @return void
     */
    public function testTimestampTzMultiRange(array $expected, string $value): void
    {
        $parser = new MultiRangeParser();
        $result = $parser->asMultiTimestampTz()->parse($value);

        $this->assertCount(count($expected), $result);

        foreach ($expected as $i => $date) {

            $res = $result[$i];

            $this->assertCount(count($date), $res);

            $lower = $res[0];
            $upper = $res[1];
            $min = $date[0];
            $max = $date[1];

            if ($min === null) {
                $this->assertNull($lower);
            } else {
                $this->assertInstanceOf(DateTimeInterface::class, $lower);
                $this->assertSame($min->format('U'), $lower->format('U'));
            }

            if ($max === null) {
                $this->assertNull($upper);
            } else {
                $this->assertInstanceOf(DateTimeInterface::class, $upper);
                $this->assertSame($max->format('U'), $upper->format('U'));
            }
        }
    }

    public static function customDataProvider(): array
    {
        return [
            [
                [
                    ['a', 'f'],
                    ['from', 'to'],
                    [null, 'to'],
                    ['from', null],
                ],
                '{[a,f],[from,to],[,to],[from,]}',
            ]
        ];
    }

    /**
     * @dataProvider customDataProvider
     * @param array $expected
     * @param string $value
     * @return void
     */
    public function testCustomMultiRange(array $expected, string $value): void
    {
        $parser = (new MultiRangeParser())->asCustom();
        $result = $parser->parse($value);

        $this->assertSame($expected, $result);
    }

    public static function exceptionDataProvider(): array
    {
        return [
            ['{,2147483649)}'],
            ['test'],
            ['{[2147483648,2147483649,[,2147483649)}}']
        ];
    }

    /**
     * @dataProvider exceptionDataProvider
     * @param string $value
     * @return void
     */
    public function testExceptions(string $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported range format');

        (new RangeParser())->asCustom()->parse($value);
    }
}
