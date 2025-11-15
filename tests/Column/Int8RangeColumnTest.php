<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests\Column;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Pgsql\Connection;
use Yiisoft\Db\Pgsql\Expression\Int8RangeValue;
use Yiisoft\Db\Pgsql\Tests\Support\TestTrait;

final class Int8RangeColumnTest extends TestCase
{
    use TestTrait;

    public static function dataBase(): iterable
    {
        yield ['[1,6)', '[1,5]'];
        yield ['[2,5)', '(1,5)'];
        yield ['[1,5)', '[1,5)'];
        yield ['[2,6)', '(1,5]'];
        yield ['[1,6)', [1, 5]];
        yield ['[1,6)', new Int8RangeValue(1, 5)];
        yield ['[2,5)', new Int8RangeValue(1, 5, false, false)];
        yield ['[1,)', '[1,)'];
        yield ['[1,)', new Int8RangeValue(1, null)];
        yield ['[1,)', new Int8RangeValue(1, null, true, false)];
        yield ['(,11)', '(,10]'];
        yield ['(,11)', new Int8RangeValue(null, 10, false, true)];
        yield ['(,)', '(,)'];
        yield ['(,)', new Int8RangeValue(null, null)];
        yield ['(,)', new Int8RangeValue(null, null, false, false)];
        yield ['(,)', [null, null]];
        yield ['[1,)', [1, null]];
        yield ['(,2)', [null, 1]];
        yield ['empty', '(7,7)'];
        yield ['empty', 'empty'];
        yield ['empty', new Int8RangeValue(10, 10, false, false)];
    }

    #[DataProvider('dataBase')]
    public function testBase(mixed $expectedColumnValue, mixed $value): void
    {
        $db = $this->createConnection(['[1,10)', '(20,30]']);

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
                col INT8RANGE
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
