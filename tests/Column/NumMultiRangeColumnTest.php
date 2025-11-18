<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests\Column;

use PHPUnit\Framework\Attributes\DataProvider;
use Yiisoft\Db\Pgsql\Connection;
use Yiisoft\Db\Pgsql\Expression\MultiRangeValue;
use Yiisoft\Db\Pgsql\Expression\NumRangeValue;
use Yiisoft\Db\Pgsql\Tests\Support\IntegrationTestTrait;
use Yiisoft\Db\Tests\Support\IntegrationTestCase;

final class NumMultiRangeColumnTest extends IntegrationTestCase
{
    use IntegrationTestTrait;

    public static function dataBase(): iterable
    {
        yield ['{[1.5,5.5],[10.5,20.5)}', '{[1.5,5.5],[10.5,20.5)}'];
        yield ['{(1.5,5.5),[10.5,20.5)}', '{(1.5,5.5),[10.5,20.5)}'];
        yield ['{[1.5,5.5),[10.5,20.5)}', '{[1.5,5.5),[10.5,20.5)}'];
        yield ['{(1.5,5.5],[10.5,20.5)}', '{(1.5,5.5],[10.5,20.5)}'];
        yield ['{[1.5,5.5],[20.5,30.5)}', '{[1.5,5.5],[20.5,30.5)}'];
        yield ['{[1.5,)}', '{[1.5,)}'];
        yield ['{(,10.5]}', '{(,10.5]}'];
        yield ['{(,)}', '{(,)}'];
        yield ['{}', '{empty}'];
        yield ['{}', '{}'];
        yield ['{[1.5,5.5],[10.5,15.5),[20.5,25.5)}', '{[1.5,5.5],[10.5,15.5),[20.5,25.5)}'];
        yield ['{[1.5,5.5],[10.5,15.5),[20.5,25.5)}', ['[1.5,5.5]', '[10.5,15.5)', '[20.5,25.5)']];
        yield ['{}', []];
        yield [
            '{[1.5,8.5)}',
            [
                new NumRangeValue(1.5, 5.5),
                new NumRangeValue(2.5, 8.5, false, false),
            ],
        ];
        yield ['{[1.5,3.5],[7.5,9.5]}', [new NumRangeValue(1.5, 3.5), new NumRangeValue(7.5, 9.5)]];
        yield ['{[1.5,5.5],[10.5,15.5),[20.5,25.5)}', new MultiRangeValue('[1.5,5.5]', '[10.5,15.5)', '[20.5,25.5)')];
        yield ['{}', new MultiRangeValue()];
        yield [
            '{[1.5,8.5)}',
            new MultiRangeValue(
                new NumRangeValue(1.5, 5.5),
                new NumRangeValue(2.5, 8.5, false, false),
            ),
        ];
    }

    #[DataProvider('dataBase')]
    public function testBase(mixed $expectedColumnValue, mixed $value): void
    {
        $db = $this->prepareConnection(['{[1.5,10.5),[20.5,30.5]}', '{[40.5,50.5)}']);

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
                new NumRangeValue(1.5, 5.5, true, true),
                new NumRangeValue(10.5, 20.5, true, false),
            ],
            '{[1.5,5.5],[10.5,20.5)}',
        ];
        yield [
            [
                new NumRangeValue(null, 5.5, false, true),
                new NumRangeValue(10.5, null, true, false),
            ],
            '{[,5.5],[10.5,)}',
        ];
    }

    #[DataProvider('dataPhpTypecast')]
    public function testPhpTypecast(array $expected, string $value): void
    {
        $db = $this->prepareConnection([$value]);

        $result = $db->select('col')->from('tbl_test')->where(['id' => 1])->withTypecasting()->one();

        $this->assertEquals($expected, $result['col']);
    }

    /**
     * @psalm-param list<string> $values
     */
    private function prepareConnection(array $values = []): Connection
    {
        $db = $this->getSharedConnection();
        $this->ensureMinPostgreSqlVersion('14.0');

        $db->createCommand('DROP TABLE IF EXISTS tbl_test')->execute();
        $db->createCommand(
            <<<SQL
            CREATE TABLE tbl_test (
                id SERIAL PRIMARY KEY,
                col NUMMULTIRANGE
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
