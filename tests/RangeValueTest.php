<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Yiisoft\Db\Pgsql\Expression\DateRangeValue;
use Yiisoft\Db\Pgsql\Expression\Int4RangeValue;
use Yiisoft\Db\Pgsql\Expression\Int8RangeValue;
use Yiisoft\Db\Pgsql\Expression\NumRangeValue;
use Yiisoft\Db\Pgsql\Expression\TsRangeValue;
use Yiisoft\Db\Pgsql\Expression\TsTzRangeValue;

use const PHP_INT_MAX;
use const PHP_INT_MIN;

final class RangeValueTest extends TestCase
{
    public static function boundsProvider(): iterable
    {
        yield 'int4 inclusive' => [
            new Int4RangeValue(1, 5),
            [1, 5],
        ];
        yield 'int4 exclusive' => [
            new Int4RangeValue(1, 5, false, false),
            [2, 4],
        ];
        yield 'int4 unbounded' => [
            new Int4RangeValue(null, null, false, false),
            [null, null],
        ];
        yield 'int8 inclusive string bounds' => [
            new Int8RangeValue(PHP_INT_MIN, PHP_INT_MAX),
            [PHP_INT_MIN, PHP_INT_MAX],
        ];
        yield 'int8 exclusive' => [
            new Int8RangeValue('1', '5', false, false),
            [2, 4],
        ];
        yield 'int8 unbounded' => [
            new Int8RangeValue(null, null, false, false),
            [null, null],
        ];
        yield 'num inclusive' => [
            new NumRangeValue(1.5, 5.5),
            [1.5, 5.5],
        ];
        yield 'num unbounded' => [
            new NumRangeValue(null, null, false, false),
            [null, null],
        ];
        yield 'date inclusive' => [
            new DateRangeValue(new DateTimeImmutable('2024-01-01'), new DateTimeImmutable('2024-01-05')),
            [new DateTimeImmutable('2024-01-01'), new DateTimeImmutable('2024-01-05')],
        ];
        yield 'date exclusive' => [
            new DateRangeValue(
                new DateTimeImmutable('2024-01-01'),
                new DateTimeImmutable('2024-01-05'),
                false,
                false,
            ),
            [new DateTimeImmutable('2024-01-02'), new DateTimeImmutable('2024-01-04')],
        ];
        yield 'timestamp exclusive' => [
            new TsRangeValue(
                new DateTimeImmutable('2024-01-01 10:00:00'),
                new DateTimeImmutable('2024-01-01 15:00:00'),
                false,
                false,
            ),
            [new DateTimeImmutable('2024-01-01 10:00:01'), new DateTimeImmutable('2024-01-01 14:59:59')],
        ];
        yield 'timestamp with time zone exclusive' => [
            new TsTzRangeValue(
                new DateTimeImmutable('2024-01-01 10:00:00+02:00'),
                new DateTimeImmutable('2024-01-01 15:00:00+02:00'),
                false,
                false,
            ),
            [new DateTimeImmutable('2024-01-01 10:00:01+02:00'), new DateTimeImmutable('2024-01-01 14:59:59+02:00')],
        ];
    }

    #[DataProvider('boundsProvider')]
    public function testGetBounds(object $rangeValue, array $expected): void
    {
        $this->assertBoundsSame($expected, $rangeValue->getBounds());
    }

    public static function boundsExceptionProvider(): iterable
    {
        yield 'int8 lower below minimum' => [
            new Int8RangeValue(PHP_INT_MIN, null, false),
            'Lower bound cannot be determined from the excluded value of a bigint range.',
        ];
        yield 'int8 lower at maximum' => [
            new Int8RangeValue(PHP_INT_MAX, null, false),
            'Lower bound cannot be determined from the excluded value of a bigint range.',
        ];
        yield 'int8 upper above maximum' => [
            new Int8RangeValue(null, PHP_INT_MAX, true, false),
            'Upper bound cannot be determined from the excluded value of a bigint range.',
        ];
        yield 'int8 upper at minimum' => [
            new Int8RangeValue(null, PHP_INT_MIN, true, false),
            'Upper bound cannot be determined from the excluded value of a bigint range.',
        ];
        yield 'num lower exclusive' => [
            new NumRangeValue(1.5, null, false),
            'Lower bound cannot be determined from the excluded value of a numeric range.',
        ];
        yield 'num upper exclusive' => [
            new NumRangeValue(null, 5.5, true, false),
            'Upper bound cannot be determined from the excluded value of a numeric range.',
        ];
    }

    #[DataProvider('boundsExceptionProvider')]
    public function testGetBoundsException(object $rangeValue, string $expectedMessage): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($expectedMessage);

        $rangeValue->getBounds();
    }

    private function assertBoundsSame(array $expected, array $actual): void
    {
        $this->assertCount(2, $actual);

        foreach ($expected as $key => $expectedValue) {
            if ($expectedValue instanceof DateTimeImmutable) {
                $this->assertEquals($expectedValue, $actual[$key]);
            } else {
                $this->assertSame($expectedValue, $actual[$key]);
            }
        }
    }
}
