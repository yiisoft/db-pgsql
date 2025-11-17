<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests\Column;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Pgsql\Connection;
use Yiisoft\Db\Pgsql\Expression\Int4RangeValue;
use Yiisoft\Db\Pgsql\Tests\Support\TestTrait;
use Yiisoft\Db\Pgsql\Tests\TestConnection;

final class Int4RangeColumnTest extends TestCase
{
    use TestTrait;

    public static function dataBase(): iterable
    {
        yield ['[1,6)', '[1,5]'];
        yield ['[2,5)', '(1,5)'];
        yield ['[1,5)', '[1,5)'];
        yield ['[2,6)', '(1,5]'];
        yield ['[1,6)', [1, 5]];
        yield ['[1,6)', new Int4RangeValue(1, 5)];
        yield ['[2,5)', new Int4RangeValue(1, 5, false, false)];
        yield ['[1,)', '[1,)'];
        yield ['[1,)', new Int4RangeValue(1, null)];
        yield ['[1,)', new Int4RangeValue(1, null, true, false)];
        yield ['(,11)', '(,10]'];
        yield ['(,11)', new Int4RangeValue(null, 10, false, true)];
        yield ['(,)', '(,)'];
        yield ['(,)', new Int4RangeValue(null, null)];
        yield ['(,)', new Int4RangeValue(null, null, false, false)];
        yield ['(,)', [null, null]];
        yield ['[1,)', [1, null]];
        yield ['(,2)', [null, 1]];
        yield ['empty', '(7,7)'];
        yield ['empty', 'empty'];
        yield ['empty', new Int4RangeValue(10, 10, false, false)];
    }

    #[DataProvider('dataBase')]
    public function testBase(mixed $expectedColumnValue, mixed $value): void
    {
        $db = $this->createConnection(['[1,10)', '(20,30]']);

        $db->createCommand()->insert('tbl_test', ['col' => $value])->execute();

        $result = $db->select('col')->from('tbl_test')->where(['id' => 3])->one();

        $this->assertIsArray($result);
        $this->assertSame($expectedColumnValue, $result['col']);
    }

    public static function dataPhpTypecast(): iterable
    {
        yield 'empty' => [null, 'empty'];
        yield [
            new Int4RangeValue(1, 6, true, false),
            '[1,5]',
        ];
        yield [
            new Int4RangeValue(null, 6, false, false),
            '(,5]',
        ];
        yield [
            new Int4RangeValue(6, null, true, false),
            '(5,)',
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
                col INT4RANGE
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
