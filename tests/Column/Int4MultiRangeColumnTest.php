<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests\Column;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Pgsql\Connection;
use Yiisoft\Db\Pgsql\Expression\Int4RangeValue;
use Yiisoft\Db\Pgsql\Expression\MultiRangeValue;
use Yiisoft\Db\Pgsql\Tests\Support\TestTrait;

final class Int4MultiRangeColumnTest extends TestCase
{
    use TestTrait;

    public static function dataBase(): iterable
    {
        yield ['{[1,6),[10,20)}', '{[1,5],[10,20)}'];
        yield ['{[2,5),[10,20)}', '{(1,5),[10,20)}'];
        yield ['{[1,5),[10,20)}', '{[1,5),[10,20)}'];
        yield ['{[2,6),[10,20)}', '{(1,5],[10,20)}'];
        yield ['{[1,6),[20,30)}', '{[1,5],[20,30)}'];
        yield ['{[1,)}', '{[1,)}'];
        yield ['{(,11)}', '{(,10]}'];
        yield ['{(,)}', '{(,)}'];
        yield ['{}', '{empty}'];
        yield ['{}', '{}'];
        yield ['{[1,6),[10,15),[20,25)}', '{[1,5],[10,15),[20,25)}'];
        yield ['{[1,6),[10,15),[20,25)}', ['[1,5]', '[10,15)', '[20,25)']];
        yield ['{}', []];
        yield [
            '{[1,8)}',
            [
                new Int4RangeValue(1, 5),
                new Int4RangeValue(2, 8, false, false),
            ]
        ];
        yield ['{[1,4),[7,10)}', [new Int4RangeValue(1, 3), new Int4RangeValue(7, 9)]];
        yield ['{[1,6),[10,15),[20,25)}', new MultiRangeValue('[1,5]', '[10,15)', '[20,25)')];
        yield ['{}', new MultiRangeValue()];
        yield [
            '{[1,8)}',
            new MultiRangeValue(
                new Int4RangeValue(1, 5),
                new Int4RangeValue(2, 8, false, false),
            )
        ];
    }

    #[DataProvider('dataBase')]
    public function testBase(mixed $expectedColumnValue, mixed $value): void
    {
        $db = $this->createConnection(['{[1,10),[20,30]}', '{[40,50)}']);

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
                col INT4MULTIRANGE
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
