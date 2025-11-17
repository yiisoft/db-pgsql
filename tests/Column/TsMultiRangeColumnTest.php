<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests\Column;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Pgsql\Connection;
use Yiisoft\Db\Pgsql\Expression\MultiRangeValue;
use Yiisoft\Db\Pgsql\Expression\TsRangeValue;
use Yiisoft\Db\Pgsql\Tests\Support\TestTrait;
use Yiisoft\Db\Pgsql\Tests\TestConnection;

final class TsMultiRangeColumnTest extends TestCase
{
    use TestTrait;

    public static function dataBase(): iterable
    {
        yield [
            '{["2024-01-01 10:00:00","2024-01-01 15:00:00"],["2024-01-02 10:00:00","2024-01-02 15:00:00")}',
            '{["2024-01-01 10:00:00","2024-01-01 15:00:00"],["2024-01-02 10:00:00","2024-01-02 15:00:00")}',
        ];
        yield [
            '{("2024-01-01 10:00:00","2024-01-01 15:00:00"),["2024-01-02 10:00:00","2024-01-02 15:00:00")}',
            '{("2024-01-01 10:00:00","2024-01-01 15:00:00"),["2024-01-02 10:00:00","2024-01-02 15:00:00")}',
        ];
        yield [
            '{["2024-01-01 10:00:00","2024-01-01 15:00:00"),["2024-01-02 10:00:00","2024-01-02 15:00:00")}',
            '{["2024-01-01 10:00:00","2024-01-01 15:00:00"),["2024-01-02 10:00:00","2024-01-02 15:00:00")}',
        ];
        yield [
            '{("2024-01-01 10:00:00","2024-01-01 15:00:00"],["2024-01-02 10:00:00","2024-01-02 15:00:00")}',
            '{("2024-01-01 10:00:00","2024-01-01 15:00:00"],["2024-01-02 10:00:00","2024-01-02 15:00:00")}',
        ];
        yield ['{["2024-01-01 10:00:00",)}', '{["2024-01-01 10:00:00",)}'];
        yield ['{(,"2024-01-10 10:00:00"]}', '{(,"2024-01-10 10:00:00"]}'];
        yield ['{(,)}', '{(,)}'];
        yield ['{}', '{empty}'];
        yield ['{}', '{}'];
        yield [
            '{["2024-01-01 10:00:00","2024-01-01 15:00:00"],["2024-01-02 10:00:00","2024-01-02 15:00:00"),["2024-01-03 10:00:00","2024-01-03 15:00:00")}',
            '{["2024-01-01 10:00:00","2024-01-01 15:00:00"],["2024-01-02 10:00:00","2024-01-02 15:00:00"),["2024-01-03 10:00:00","2024-01-03 15:00:00")}',
        ];
        yield [
            '{["2024-01-01 10:00:00","2024-01-01 15:00:00"],["2024-01-02 10:00:00","2024-01-02 15:00:00"),["2024-01-03 10:00:00","2024-01-03 15:00:00")}',
            ['["2024-01-01 10:00:00","2024-01-01 15:00:00"]', '["2024-01-02 10:00:00","2024-01-02 15:00:00")', '["2024-01-03 10:00:00","2024-01-03 15:00:00")'],
        ];
        yield ['{}', []];
        yield [
            '{["2024-01-01 10:00:00","2024-01-01 15:00:00"],("2024-01-02 10:00:00","2024-01-02 15:00:00")}',
            [
                new TsRangeValue(new DateTimeImmutable('2024-01-01 10:00:00'), new DateTimeImmutable('2024-01-01 15:00:00')),
                new TsRangeValue(new DateTimeImmutable('2024-01-02 10:00:00'), new DateTimeImmutable('2024-01-02 15:00:00'), false, false),
            ],
        ];
        yield [
            '{["2024-01-01 10:00:00","2024-01-01 13:00:00"],["2024-01-02 10:00:00","2024-01-02 13:00:00"]}',
            [
                new TsRangeValue(new DateTimeImmutable('2024-01-01 10:00:00'), new DateTimeImmutable('2024-01-01 13:00:00')),
                new TsRangeValue(new DateTimeImmutable('2024-01-02 10:00:00'), new DateTimeImmutable('2024-01-02 13:00:00')),
            ],
        ];
        yield [
            '{["2024-01-01 10:00:00","2024-01-01 15:00:00"],["2024-01-02 10:00:00","2024-01-02 15:00:00"),["2024-01-03 10:00:00","2024-01-03 15:00:00")}',
            new MultiRangeValue('["2024-01-01 10:00:00","2024-01-01 15:00:00"]', '["2024-01-02 10:00:00","2024-01-02 15:00:00")', '["2024-01-03 10:00:00","2024-01-03 15:00:00")'),
        ];
        yield ['{}', new MultiRangeValue()];
        yield [
            '{["2024-01-01 10:00:00","2024-01-01 15:00:00"],("2024-01-02 10:00:00","2024-01-02 15:00:00")}',
            new MultiRangeValue(
                new TsRangeValue(new DateTimeImmutable('2024-01-01 10:00:00'), new DateTimeImmutable('2024-01-01 15:00:00')),
                new TsRangeValue(new DateTimeImmutable('2024-01-02 10:00:00'), new DateTimeImmutable('2024-01-02 15:00:00'), false, false),
            ),
        ];
    }

    #[DataProvider('dataBase')]
    public function testBase(mixed $expectedColumnValue, mixed $value): void
    {
        $db = $this->createConnection(['{["2024-01-01 10:00:00","2024-01-01 20:00:00"),["2024-01-02 10:00:00","2024-01-02 20:00:00"]}', '{["2024-01-03 10:00:00","2024-01-03 20:00:00")}']);

        $db->createCommand()->insert('tbl_test', ['col' => $value])->execute();

        $result = $db->select('col')->from('tbl_test')->where(['id' => 3])->one();

        $this->assertIsArray($result);
        $this->assertSame($expectedColumnValue, $result['col']);
    }

    public static function dataPhpTypecast(): iterable
    {
        yield 'empty' => [[], '{}'];
        yield [
            [
                new TsRangeValue(new DateTimeImmutable('2024-01-01 10:00:00'), new DateTimeImmutable('2024-01-01 15:00:00'), true, true),
                new TsRangeValue(new DateTimeImmutable('2024-01-02 10:00:00'), new DateTimeImmutable('2024-01-02 15:00:00'), true, false),
            ],
            '{["2024-01-01 10:00:00","2024-01-01 15:00:00"],["2024-01-02 10:00:00","2024-01-02 15:00:00")}',
        ];
        yield [
            [
                new TsRangeValue(null, new DateTimeImmutable('2024-01-01 15:00:00'), false, true),
                new TsRangeValue(new DateTimeImmutable('2024-01-02 10:00:00'), null, true, false),
            ],
            '{[,"2024-01-01 15:00:00"],["2024-01-02 10:00:00",)}',
        ];
    }

    #[DataProvider('dataPhpTypecast')]
    public function testPhpTypecast(array $expected, string $value): void
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
        $this->ensureMinPostgreSqlVersion('14.0');

        $db->createCommand('DROP TABLE IF EXISTS tbl_test')->execute();
        $db->createCommand(
            <<<SQL
            CREATE TABLE tbl_test (
                id SERIAL PRIMARY KEY,
                col TSMULTIRANGE
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
