<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests\Column;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use Yiisoft\Db\Pgsql\Connection;
use Yiisoft\Db\Pgsql\Expression\DateRangeValue;
use Yiisoft\Db\Pgsql\Tests\Support\IntegrationTestTrait;
use Yiisoft\Db\Tests\Support\IntegrationTestCase;

final class DateRangeColumnTest extends IntegrationTestCase
{
    use IntegrationTestTrait;

    public static function dataBase(): iterable
    {
        yield ['[2024-01-01,2024-01-06)', '[2024-01-01,2024-01-05]'];
        yield ['[2024-01-02,2024-01-05)', '(2024-01-01,2024-01-05)'];
        yield ['[2024-01-01,2024-01-05)', '[2024-01-01,2024-01-05)'];
        yield ['[2024-01-02,2024-01-06)', '(2024-01-01,2024-01-05]'];
        yield [
            '[2024-01-01,2024-01-06)',
            new DateRangeValue(
                new DateTimeImmutable('2024-01-01'),
                new DateTimeImmutable('2024-01-05'),
            ),
        ];
        yield [
            '[2024-01-02,2024-01-05)',
            new DateRangeValue(
                new DateTimeImmutable('2024-01-01'),
                new DateTimeImmutable('2024-01-05'),
                false,
                false,
            ),
        ];
        yield ['[2024-01-01,)', '[2024-01-01,)'];
        yield [
            '[2024-01-01,)',
            new DateRangeValue(new DateTimeImmutable('2024-01-01'), null),
        ];
        yield [
            '[2024-01-01,)',
            new DateRangeValue(new DateTimeImmutable('2024-01-01'), null, true, false),
        ];
        yield ['(,2024-01-11)', '(,2024-01-10]'];
        yield [
            '(,2024-01-11)',
            new DateRangeValue(null, new DateTimeImmutable('2024-01-10'), false, true),
        ];
        yield ['(,)', '(,)'];
        yield [
            '(,)',
            new DateRangeValue(null, null),
        ];
        yield [
            '(,)',
            new DateRangeValue(null, null, false, false),
        ];
        yield ['empty', '(2024-01-07,2024-01-07)'];
        yield ['empty', 'empty'];
        yield [
            'empty',
            new DateRangeValue(
                new DateTimeImmutable('2024-01-10'),
                new DateTimeImmutable('2024-01-10'),
                false,
                false,
            ),
        ];
    }

    #[DataProvider('dataBase')]
    public function testBase(mixed $expectedColumnValue, mixed $value): void
    {
        $db = $this->prepareConnection(['[2024-01-01,2024-01-10)', '(2024-01-20,2024-01-30]']);

        $db->createCommand()->insert('tbl_test', ['col' => $value])->execute();

        $result = $db->select('col')->from('tbl_test')->where(['id' => 3])->one();

        $this->assertIsArray($result);
        $this->assertSame($expectedColumnValue, $result['col']);
    }

    public static function dataPhpTypecast(): iterable
    {
        yield 'empty' => [null, 'empty'];
        yield [
            new DateRangeValue(new DateTimeImmutable('2024-01-01'), new DateTimeImmutable('2024-01-06'), true, false),
            '[2024-01-01,2024-01-05]',
        ];
        yield [
            new DateRangeValue(null, new DateTimeImmutable('2024-01-06'), false, false),
            '(,2024-01-05]',
        ];
        yield [
            new DateRangeValue(new DateTimeImmutable('2024-01-06'), null, true, false),
            '(2024-01-05,)',
        ];
    }

    #[DataProvider('dataPhpTypecast')]
    public function testPhpTypecast(mixed $expected, string $value): void
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

        $db->createCommand('DROP TABLE IF EXISTS tbl_test')->execute();
        $db->createCommand(
            <<<SQL
            CREATE TABLE tbl_test (
                id SERIAL PRIMARY KEY,
                col DATERANGE
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
