<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests\Column;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Pgsql\Connection;
use Yiisoft\Db\Pgsql\Expression\DateRangeValue;
use Yiisoft\Db\Pgsql\Expression\MultiRangeValue;
use Yiisoft\Db\Pgsql\Tests\Support\TestTrait;

final class DateMultiRangeColumnTest extends TestCase
{
    use TestTrait;

    public static function dataBase(): iterable
    {
        yield [
            '{[2024-01-01,2024-01-06),[2024-02-01,2024-02-10)}',
            '{[2024-01-01,2024-01-05],[2024-02-01,2024-02-10)}',
        ];
        yield [
            '{[2024-01-02,2024-01-05),[2024-02-01,2024-02-10)}',
            '{(2024-01-01,2024-01-05),[2024-02-01,2024-02-10)}',
        ];
        yield [
            '{[2024-01-01,2024-01-05),[2024-02-01,2024-02-10)}',
            '{[2024-01-01,2024-01-05),[2024-02-01,2024-02-10)}',
        ];
        yield [
            '{[2024-01-02,2024-01-06),[2024-02-01,2024-02-10)}',
            '{(2024-01-01,2024-01-05],[2024-02-01,2024-02-10)}',
        ];
        yield [
            '{[2024-01-01,2024-01-06),[2024-03-01,2024-03-10)}',
            '{[2024-01-01,2024-01-05],[2024-03-01,2024-03-10)}',
        ];
        yield [
            '{[2024-01-01,)}',
            '{[2024-01-01,)}',
        ];
        yield [
            '{(,2024-01-11)}',
            '{(,2024-01-10]}',
        ];
        yield [
            '{(,)}',
            '{(,)}',
        ];
        yield [
            '{}',
            '{empty}',
        ];
        yield [
            '{}',
            '{}',
        ];
        yield [
            '{[2024-01-01,2024-01-06),[2024-02-01,2024-02-05),[2024-03-01,2024-03-05)}',
            '{[2024-01-01,2024-01-05],[2024-02-01,2024-02-05),[2024-03-01,2024-03-05)}',
        ];
        yield [
            '{[2024-01-01,2024-01-06),[2024-02-01,2024-02-05),[2024-03-01,2024-03-05)}',
            ['[2024-01-01,2024-01-05]', '[2024-02-01,2024-02-05)', '[2024-03-01,2024-03-05)'],
        ];
        yield [
            '{}',
            [],
        ];
        yield [
            '{[2024-01-01,2024-01-08)}',
            [
                new DateRangeValue(new DateTimeImmutable('2024-01-01'), new DateTimeImmutable('2024-01-05')),
                new DateRangeValue(new DateTimeImmutable('2024-01-02'), new DateTimeImmutable('2024-01-08'), false, false),
            ],
        ];
        yield [
            '{[2024-01-01,2024-01-04),[2024-02-01,2024-02-04)}',
            [
                new DateRangeValue(new DateTimeImmutable('2024-01-01'), new DateTimeImmutable('2024-01-03')),
                new DateRangeValue(new DateTimeImmutable('2024-02-01'), new DateTimeImmutable('2024-02-03')),
            ],
        ];
        yield [
            '{[2024-01-01,2024-01-06),[2024-02-01,2024-02-05),[2024-03-01,2024-03-05)}',
            new MultiRangeValue('[2024-01-01,2024-01-05]', '[2024-02-01,2024-02-05)', '[2024-03-01,2024-03-05)'),
        ];
        yield [
            '{}',
            new MultiRangeValue(),
        ];
        yield [
            '{[2024-01-01,2024-01-08)}',
            new MultiRangeValue(
                new DateRangeValue(new DateTimeImmutable('2024-01-01'), new DateTimeImmutable('2024-01-05')),
                new DateRangeValue(new DateTimeImmutable('2024-01-02'), new DateTimeImmutable('2024-01-08'), false, false),
            ),
        ];
    }

    #[DataProvider('dataBase')]
    public function testBase(mixed $expectedColumnValue, mixed $value): void
    {
        $db = $this->createConnection(['{[2024-01-01,2024-01-10),[2024-01-20,2024-01-30]}', '{[2024-02-01,2024-02-10)}']);

        $db->createCommand()->insert('tbl_test', ['col' => $value])->execute();

        $result = $db->select('col')->from('tbl_test')->where(['id' => 3])->one();

        $db->close();

        $this->assertIsArray($result);
        $this->assertSame($expectedColumnValue, $result['col']);
    }

    /**
     * @psalm-param list<string> $values
     */
    private function createConnection(array $values = []): Connection
    {
        $db = $this->getConnection(minVersion: '14.0');

        $db->createCommand('DROP TABLE IF EXISTS tbl_test')->execute();
        $db->createCommand(
            <<<SQL
            CREATE TABLE tbl_test (
                id SERIAL PRIMARY KEY,
                col DATEMULTIRANGE
            );
            SQL,
        )->execute();

        if ($values !== []) {
            $valuesClause = implode(
                ', ',
                array_map(
                    static fn(string $value): string => "('$value')",
                    $values,
                ),
            );
            $db
                ->createCommand("INSERT INTO tbl_test (col) VALUES $valuesClause")
                ->execute();
        }

        return $db;
    }
}
