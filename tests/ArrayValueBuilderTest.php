<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use ArrayIterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Constant\DataType;
use Yiisoft\Db\Expression\Value\ArrayValue;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\Value\JsonValue;
use Yiisoft\Db\Expression\Value\Param;
use Yiisoft\Db\Expression\Value\StructuredValue;
use Yiisoft\Db\Pgsql\Builder\ArrayValueBuilder;
use Yiisoft\Db\Pgsql\Column\ColumnBuilder;
use Yiisoft\Db\Pgsql\Data\LazyArray;
use Yiisoft\Db\Pgsql\Data\StructuredLazyArray;
use Yiisoft\Db\Pgsql\Tests\Support\TestTrait;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Schema\Column\ColumnInterface;
use Yiisoft\Db\Schema\Data\JsonLazyArray;
use Yiisoft\Db\Schema\Data\LazyArrayInterface;
use Yiisoft\Db\Tests\Support\Assert;

/**
 * @group pgsql
 */
final class ArrayValueBuilderTest extends TestCase
{
    use TestTrait;

    public static function buildProvider(): array
    {
        return [
            'null' => [null, null, 'NULL', []],
            'empty' => [[], null, 'ARRAY[]', []],
            'array w/o type' => [
                [[true], [1], ['a'], null],
                null,
                'ARRAY[ARRAY[TRUE]::bool[],ARRAY[1]::int[],ARRAY[:qp0]::text[],NULL]::jsonb[]',
                [
                    ':qp0' => new Param('a', DataType::STRING),
                ],
            ],
            'bool w/o type' => [[true, false, null], null, 'ARRAY[TRUE,FALSE,NULL]::bool[]'],
            'int w/o type' => [[1, 2, 3], null, 'ARRAY[1,2,3]::int[]'],
            'float w/o type' => [[1.2, 2.0, null], null, 'ARRAY[1.2,2,NULL]'],
            'string w/o type' => [
                ['a', 'b', 'c'],
                null,
                'ARRAY[:qp0,:qp1,:qp2]::text[]',
                [
                    ':qp0' => new Param('a', DataType::STRING),
                    ':qp1' => new Param('b', DataType::STRING),
                    ':qp2' => new Param('c', DataType::STRING),
                ],
            ],
            'resource w/o type' => [
                [$resource = fopen('php://memory', 'rb'), null],
                null,
                'ARRAY[:qp0,NULL]::bytea[]',
                [
                    ':qp0' => new Param($resource, DataType::LOB),
                ],
            ],
            'ArrayIterator w/o type' => [new ArrayIterator([1, 2, 3]), null, 'ARRAY[1,2,3]::int[]'],
            'ArrayIterator' => [
                new ArrayIterator(['a', 'b', 'c']),
                'varchar',
                'ARRAY[:qp0,:qp1,:qp2]::varchar[]',
                [
                    ':qp0' => new Param('a', DataType::STRING),
                    ':qp1' => new Param('b', DataType::STRING),
                    ':qp2' => new Param('c', DataType::STRING),
                ],
            ],
            'LazyArray' => [
                new LazyArray('{1,2,3}'),
                'int[]',
                ':qp0::int[]',
                [':qp0' => new Param('{1,2,3}', DataType::STRING)],
            ],
            'LazyArray external' => [
                new \Yiisoft\Db\Schema\Data\LazyArray('[1,2,3]'),
                ColumnBuilder::integer(),
                'ARRAY[1,2,3]::integer[]',
            ],
            'StructuredLazyArray' => [
                new StructuredLazyArray('(1,2,3)'),
                'int',
                'ARRAY[1,2,3]::int[]',
            ],
            'JsonLazyArray' => [
                new JsonLazyArray('[1,2,3]'),
                ColumnBuilder::array(ColumnBuilder::integer()),
                'ARRAY[1,2,3]::integer[]',
            ],
            'Expression' => [[new Expression('now()')], null, 'ARRAY[now()]'],
            'JsonValue w/o type' => [
                [new JsonValue(['a' => null, 'b' => 123, 'c' => [4, 5]]), new JsonValue([true])],
                null,
                'ARRAY[:qp0,:qp1]',
                [
                    ':qp0' => new Param('{"a":null,"b":123,"c":[4,5]}', DataType::STRING),
                    ':qp1' => new Param('[true]', DataType::STRING),
                ],
            ],
            'JsonValue' => [
                [new JsonValue(['a' => null, 'b' => 123, 'c' => [4, 5]]), new JsonValue([true])],
                'jsonb',
                'ARRAY[:qp0,:qp1]::jsonb[]',
                [
                    ':qp0' => new Param('{"a":null,"b":123,"c":[4,5]}', DataType::STRING),
                    ':qp1' => new Param('[true]', DataType::STRING),
                ],
            ],
            'StructuredValue' => [
                [
                    null,
                    new StructuredValue(['value' => 11.11, 'currency_code' => 'USD']),
                    new StructuredValue(['value' => null, 'currency_code' => null]),
                ],
                null,
                'ARRAY[NULL,ROW(11.11,:qp0),ROW(NULL,NULL)]',
                [':qp0' => new Param('USD', DataType::STRING)],
            ],
            'Query w/o type' => [
                (new Query(self::getDb()))->select('id')->from('users')->where(['active' => 1]),
                null,
                'ARRAY(SELECT "id" FROM "users" WHERE "active" = 1)',
            ],
            'Query' => [
                [(new Query(self::getDb()))->select('id')->from('users')->where(['active' => 1])],
                'integer[][]',
                'ARRAY[ARRAY(SELECT "id" FROM "users" WHERE "active" = 1)::integer[]]::integer[][]',
            ],
            'bool' => [
                [[[true], [false, null]], [['t', 'f'], null], null],
                'bool[][][]',
                'ARRAY[ARRAY[ARRAY[TRUE]::bool[],ARRAY[FALSE,NULL]::bool[]]::bool[][],ARRAY[ARRAY[TRUE,TRUE]::bool[],NULL]::bool[][],NULL]::bool[][][]',
            ],
            'associative' => [
                ['a' => '1', 'b' => null],
                ColumnType::STRING,
                'ARRAY[:qp0,NULL]::varchar(255)[]',
                [':qp0' => new Param('1', DataType::STRING)],
            ],
            'string' => [
                '{1,2,3}',
                'string[]',
                ':qp0::varchar(255)[]',
                [':qp0' => new Param('{1,2,3}', DataType::STRING)],
            ],
            'null multi-level' => [
                [[1, null], null],
                'int[][]',
                'ARRAY[ARRAY[1,NULL]::int[],NULL]::int[][]',
            ],
        ];
    }

    #[DataProvider('buildProvider')]
    public function testBuild(
        iterable|LazyArrayInterface|Query|string|null $value,
        ColumnInterface|string|null $type,
        string $expected,
        array $expectedParams = [],
    ): void {
        $db = $this->getConnection();
        $qb = $db->getQueryBuilder();

        $params = [];
        $builder = new ArrayValueBuilder($qb);
        $expression = new ArrayValue($value, $type);

        $this->assertSame($expected, $builder->build($expression, $params));
        Assert::arraysEquals($expectedParams, $params);

        $db->close();
    }
}
