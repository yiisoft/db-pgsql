<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use DateTime;
use DateTimeInterface;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Pgsql\RangeParser;
use Yiisoft\Db\Pgsql\Schema;
use const PHP_INT_SIZE;

final class RangeParserTest extends TestCase
{
    private const TYPES = [
        Schema::TYPE_INT_RANGE,
        Schema::TYPE_BIGINT_RANGE,
        Schema::TYPE_TS_TZ_RANGE,
        Schema::TYPE_TS_RANGE,
        Schema::TYPE_DATE_RANGE,
        Schema::TYPE_NUM_RANGE,
    ];

    public static function emptyDataProvider(): array
    {
        return [
            ['empty'],
            [null],
        ];
    }

    /**
     * @dataProvider emptyDataProvider
     * @return void
     */
    public function testEmptyResult(?string $value): void
    {
        $parser = new RangeParser('test');
        $result = $parser->parse($value);

        $this->assertNull($result);
        $this->assertNull($parser->asCustom()->parse($value));

        foreach (self::TYPES as $type) {
            $this->assertNull($parser->withType($type)->parse($value));

            switch ($type) {
                case Schema::TYPE_INT_RANGE:
                    $this->assertNull($parser->asInt()->parse($value));
                    break;
                case Schema::TYPE_BIGINT_RANGE:
                    $this->assertNull($parser->asBigInt()->parse($value));
                    break;
                case Schema::TYPE_NUM_RANGE:
                    $this->assertNull($parser->asNumeric()->parse($value));
                    break;
                case Schema::TYPE_DATE_RANGE:
                    $this->assertNull($parser->asDate()->parse($value));
                    break;
                case Schema::TYPE_TS_RANGE:
                    $this->assertNull($parser->asTimestamp()->parse($value));
                    break;
                case Schema::TYPE_TS_TZ_RANGE:
                    $this->assertNull($parser->asTimestampTz()->parse($value));
                    break;
            }
        }
    }

    public static function intRangeDataProvider(): array
    {
        return [
            [
                [1, 10],
                '[1,11)',
            ],

            [
                [100, 120],
                '[100,120]',
            ],

            [
                [null, null],
                '(,)',
            ],

            [
                [0, 5],
                '(-1,6)',
            ],

            [
                [5, null],
                '[5,)',
            ],

            [
                [null, 7],
                '[,8)',
            ],
        ];
    }

    /**
     * @dataProvider intRangeDataProvider
     * @return void
     * @throws \Throwable
     * @throws \Yiisoft\Db\Exception\Exception
     * @throws \Yiisoft\Db\Exception\InvalidConfigException
     */
    public function testIntRangeParser(array $expected, string $value): void
    {
        $parser = new RangeParser();
        $result = $parser->asInt()->parse($value);

        $this->assertSame($expected, $result);
    }

    public static function bigIntDataProvider(): array
    {
        return [
            [
                [
                    PHP_INT_SIZE === 8 ? (int) '2147483648' : (float) '2147483648',
                    PHP_INT_SIZE === 8 ? (int) '2147483649' : (float) '2147483649',
                ],
                '[2147483648,2147483649]',
            ],

            [
                [
                    null,
                    PHP_INT_SIZE === 8 ? (int) '2147483648' : (float) '2147483648',
                ],
                '[,2147483649)',
            ],
        ];
    }

    /**
     * @dataProvider bigIntDataProvider
     * @param string $value
     * @return void
     */
    public function testBigIntRange(array $expected, string $value): void
    {
        $parser = (new RangeParser())->asBigInt();
        $result = $parser->parse($value);

        $this->assertSame($expected, $result);
    }

    public static function numRangeDataProvider(): array
    {
        return [
            [
                [10.5, 20.7],
                '[10.5,20.7]',
            ],

            [
                [null, 39.3],
                '(,39.3]',
            ],

            [
                [11.2, null],
                '(11.2,)',
            ],

            [
                [null, null],
                '[,]',
            ],
        ];
    }

    /**
     * @dataProvider numRangeDataProvider
     * @param array $expected
     * @param string $value
     * @return void
     */
    public function testNumRangeParser(array $expected, string $value): void
    {
        $parser = new RangeParser();
        $result = $parser->asNumeric()->parse($value);

        $this->assertSame($expected, $result);
    }


    public static function dateRangeDataProvider(): array
    {
        return [
            [
                new DateTime('2020-12-01'),
                new DateTime('2021-01-01'),
                '[2020-12-01,2021-01-01]',
            ],

            [
                new DateTime('2020-12-01'),
                new DateTime('2021-01-02'),
                '[2020-12-01,2021-01-03)',
            ],

            [
                new DateTime('2021-01-01'),
                new DateTime('2021-01-02'),
                '(2020-12-31,2021-01-03)',
            ],

            [
                null,
                new DateTime('2021-01-02'),
                '(,2021-01-03)',
            ],

            [
                new DateTime('2020-12-01'),
                null,
                '[2020-12-01,)',
            ],
        ];
    }

    /**
     * @dataProvider dateRangeDataProvider
     * @param DateTimeInterface|null $lower
     * @param DateTimeInterface|null $upper
     * @param string $value
     * @return void
     */
    public function testDateRangeParser(?DateTimeInterface $lower, ?DateTimeInterface $upper, string $value): void
    {
        $parser = new RangeParser(Schema::TYPE_DATE_RANGE);
        $result = $parser->parse($value);

        $this->assertCount(2, $result);

        $min = $result[0];
        $max = $result[1];

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

    public static function timestampDataProvider(): array
    {
        return [
            [
                [
                    new DateTime('2023-08-07 13:00:00'),
                    new DateTime('2023-08-07 13:30:00'),
                ],
                '[2023-08-07 13:00:00,2023-08-07 13:30:00]'
            ],
            [
                [
                    null,
                    new DateTime('2023-08-07 13:30:00'),
                ],
                '[,2023-08-07 13:30:00]'
            ],
            [
                [
                    new DateTime('2023-08-07 13:00:00'),
                    null,
                ],
                '[2023-08-07 13:00:00,]'
            ],
        ];
    }

    /**
     * @dataProvider timestampDataProvider
     * @param DateTime[]|null[] $expected
     * @param string $value
     * @return void
     */
    public function testTimestampRange(array $expected, string $value): void
    {
        $parser = new RangeParser();
        $result = $parser->asTimestamp()->parse($value);

        $this->assertCount(2, $result);

        $lower = $result[0];
        $upper = $result[1];
        $min = $expected[0];
        $max = $expected[1];

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

    public static function timestampTzDataProvider(): array
    {
        return [
            [
                [
                    DateTime::createFromFormat('Y-m-d H:i:sP', '2023-08-07 13:00:00+03'),
                    DateTime::createFromFormat('Y-m-d H:i:sP', '2023-08-07 13:30:00+03'),
                ],
                '[2023-08-07 13:00:00+03,2023-08-07 13:30:00+03]'
            ],
            [
                [
                    null,
                    DateTime::createFromFormat('Y-m-d H:i:sP', '2023-08-07 13:30:00+03'),
                ],
                '[,2023-08-07 13:30:00+03]'
            ],
            [
                [
                    DateTime::createFromFormat('Y-m-d H:i:sP', '2023-08-07 13:00:00+03'),
                    null,
                ],
                '[2023-08-07 13:00:00+03,]'
            ],
        ];
    }

    /**
     * @dataProvider timestampTzDataProvider
     * @param DateTimeInterface[]|null[] $expected
     * @param string $value
     * @return void
     */
    public function testTimestampTzRange(array $expected, string $value): void
    {
        $parser = new RangeParser();
        $result = $parser->asTimestampTz()->parse($value);

        $this->assertCount(2, $result);

        $lower = $result[0];
        $upper = $result[1];
        $min = $expected[0];
        $max = $expected[1];

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

    public static function customDataProvider(): array
    {
        return [
            [
                ['a', 'f'],
                '[a,f]',
            ],

            [
                ['from', 'to'],
                '[from,to]',
            ],

            [
                [null, 'to'],
                '[,to]',
            ],

            [
                ['from', null],
                '[from,]',
            ]
        ];
    }

    /**
     * @dataProvider customDataProvider
     * @param array $expected
     * @param string $value
     * @return void
     */
    public function testCustomRange(array $expected, string $value): void
    {
        $parser = new RangeParser('my_custom_type');
        $result = $parser->parse($value);
        $customResult = $parser->asCustom()->parse($value);

        $this->assertSame($expected, $result);
        $this->assertSame($expected, $customResult);
    }

    public static function exceptionDataProvider(): array
    {
        return [
            ['(10,15'],
            ['test'],
            ['{10,15}'],
            ['11,]'],
        ];
    }

    /**
     * @dataProvider exceptionDataProvider
     * @param string $value
     * @return void
     */
    public function testArgumentException(string $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported range format');

        (new RangeParser())->parse($value);
    }
}
