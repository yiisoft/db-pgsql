<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests\Column;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Pgsql\Connection;
use Yiisoft\Db\Pgsql\Expression\TsTzRangeValue;
use Yiisoft\Db\Pgsql\Tests\Support\TestTrait;
use Yiisoft\Db\Pgsql\Tests\TestConnection;

final class TsTzRangeColumnTest extends TestCase
{
    use TestTrait;

    public static function dataBase(): iterable
    {
        yield [
            '["2024-01-01 10:00:00+00","2024-01-01 10:00:00+00"]',
            '["2024-01-01 10:00:00+00","2024-01-01 15:00:00+05"]',
        ];
        yield [
            '("2024-01-01 10:00:00+00","2024-01-01 15:00:00+00")',
            '("2024-01-01 10:00:00+00","2024-01-01 15:00:00+00")',
        ];
        yield [
            '["2024-01-01 10:00:00+00","2024-01-01 15:00:00+00")',
            '["2024-01-01 10:00:00+00","2024-01-01 15:00:00+00")',
        ];
        yield [
            '("2024-01-01 10:00:00+00","2024-01-01 15:00:00+00"]',
            '("2024-01-01 10:00:00+00","2024-01-01 15:00:00+00"]',
        ];
        yield [
            '["2024-01-01 10:00:00+00","2024-01-01 15:00:00+00"]',
            new TsTzRangeValue(
                new DateTimeImmutable('2024-01-01 10:00:00+00'),
                new DateTimeImmutable('2024-01-01 15:00:00+00'),
            ),
        ];
        yield [
            '("2024-01-01 10:00:00+00","2024-01-01 15:00:00+00")',
            new TsTzRangeValue(
                new DateTimeImmutable('2024-01-01 10:00:00+00'),
                new DateTimeImmutable('2024-01-01 15:00:00+00'),
                false,
                false,
            ),
        ];
        yield [
            '["2024-01-01 10:00:00+00",)',
            '["2024-01-01 10:00:00+00",)',
        ];
        yield [
            '["2024-01-01 10:00:00+00",)',
            new TsTzRangeValue(new DateTimeImmutable('2024-01-01 10:00:00+00'), null),
        ];
        yield [
            '["2024-01-01 10:00:00+00",)',
            new TsTzRangeValue(new DateTimeImmutable('2024-01-01 10:00:00+00'), null, true, false),
        ];
        yield [
            '(,"2024-01-10 10:00:00+00"]',
            '(,"2024-01-10 10:00:00+00"]',
        ];
        yield [
            '(,"2024-01-10 10:00:00+00"]',
            new TsTzRangeValue(null, new DateTimeImmutable('2024-01-10 10:00:00+00'), false, true),
        ];
        yield [
            '(,)',
            '(,)',
        ];
        yield [
            '(,)',
            new TsTzRangeValue(null, null),
        ];
        yield [
            '(,)',
            new TsTzRangeValue(null, null, false, false),
        ];
        yield [
            'empty',
            '("2024-01-07 10:00:00+00","2024-01-07 10:00:00+00")',
        ];
        yield [
            'empty',
            'empty',
        ];
        yield [
            'empty',
            new TsTzRangeValue(
                new DateTimeImmutable('2024-01-10 10:00:00+00'),
                new DateTimeImmutable('2024-01-10 10:00:00+00'),
                false,
                false,
            ),
        ];
    }

    #[DataProvider('dataBase')]
    public function testBase(mixed $expectedColumnValue, mixed $value): void
    {
        $db = $this->createConnection(['["2024-01-01 10:00:00+00","2024-01-01 20:00:00+00")', '("2024-01-01 20:00:00+00","2024-01-01 23:00:00+00"]']);

        $db->createCommand()->insert('tbl_test', ['col' => $value])->execute();

        $result = $db->select('col')->from('tbl_test')->where(['id' => 3])->one();

        $this->assertIsArray($result);
        $this->assertSame($expectedColumnValue, $result['col']);
    }

    public static function dataPhpTypecast(): iterable
    {
        yield 'empty' => [null, 'empty'];
        yield [
            new TsTzRangeValue(new DateTimeImmutable('2024-01-01 10:00:00+00'), new DateTimeImmutable('2024-01-01 15:00:00+00'), true, true),
            '["2024-01-01 10:00:00+00","2024-01-01 15:00:00+00"]',
        ];
        yield [
            new TsTzRangeValue(null, new DateTimeImmutable('2024-01-01 15:00:00+00'), false, true),
            '(,"2024-01-01 15:00:00+00"]',
        ];
        yield [
            new TsTzRangeValue(new DateTimeImmutable('2024-01-01 15:00:00+00'), null, false, false),
            '("2024-01-01 15:00:00+00",)',
        ];
    }

    #[DataProvider('dataPhpTypecast')]
    public function testPhpTypecast(mixed $expected, string $value): void
    {
        $db = $this->createConnection([$value]);

        $result = $db->select('col')->from('tbl_test')->where(['id' => 1])->withTypecasting()->one();

        $this->assertEquals($expected, $result['col']);
    }

    /**
     * @psalm-param list<string> $values
     */
    private function createConnection(array $values = []): Connection
    {
        $db = TestConnection::get();

        $db->createCommand('DROP TABLE IF EXISTS tbl_test')->execute();
        $db->createCommand(
            <<<SQL
            CREATE TABLE tbl_test (
                id SERIAL PRIMARY KEY,
                col TSTZRANGE
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
