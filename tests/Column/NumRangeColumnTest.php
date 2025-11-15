<?php

declare(strict_types=1);

namespace Column;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Pgsql\Connection;
use Yiisoft\Db\Pgsql\Expression\NumRangeValue;
use Yiisoft\Db\Pgsql\Tests\Support\TestTrait;

final class NumRangeColumnTest extends TestCase
{
    use TestTrait;

    public static function dataBase(): iterable
    {
        yield ['[1.5,5.5]', '[1.5,5.5]'];
        yield ['(1.5,5.5)', '(1.5,5.5)'];
        yield ['[1.5,5.5)', '[1.5,5.5)'];
        yield ['(1.5,5.5]', '(1.5,5.5]'];
        yield ['[1.5,5.5]', [1.5, 5.5]];
        yield ['[1.5,5.5]', new NumRangeValue(1.5, 5.5)];
        yield ['(1.5,5.5)', new NumRangeValue(1.5, 5.5, false, false)];
        yield ['[1.5,)', '[1.5,)'];
        yield ['[1.5,)', new NumRangeValue(1.5, null)];
        yield ['[1.5,)', new NumRangeValue(1.5, null, true, false)];
        yield ['(,10.5]', '(,10.5]'];
        yield ['(,10.5]', new NumRangeValue(null, 10.5, false, true)];
        yield ['(,)', '(,)'];
        yield ['(,)', new NumRangeValue(null, null)];
        yield ['(,)', new NumRangeValue(null, null, false, false)];
        yield ['(,)', [null, null]];
        yield ['[1.5,)', [1.5, null]];
        yield ['(,1.5]', [null, 1.5]];
        yield ['empty', '(7.5,7.5)'];
        yield ['empty', 'empty'];
        yield ['empty', new NumRangeValue(10.5, 10.5, false, false)];
    }

    #[DataProvider('dataBase')]
    public function testBase(mixed $expectedColumnValue, mixed $value): void
    {
        $db = $this->createConnection(['[1.5,10.5)', '(20.5,30.5]']);

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
        $db = $this->getConnection();

        $db->createCommand('DROP TABLE IF EXISTS tbl_test')->execute();
        $db->createCommand(
            <<<SQL
            CREATE TABLE tbl_test (
                id SERIAL PRIMARY KEY,
                col NUMRANGE
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
