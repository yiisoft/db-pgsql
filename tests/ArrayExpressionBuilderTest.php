<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use ArrayIterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Command\Param;
use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Constant\DataType;
use Yiisoft\Db\Expression\ArrayExpression;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\JsonExpression;
use Yiisoft\Db\Expression\StructuredExpression;
use Yiisoft\Db\Pgsql\Builder\ArrayExpressionBuilder;
use Yiisoft\Db\Pgsql\Column\ColumnBuilder;
use Yiisoft\Db\Pgsql\Data\LazyArray;
use Yiisoft\Db\Pgsql\Data\StructuredLazyArray;
use Yiisoft\Db\Pgsql\Tests\Support\TestTrait;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Schema\Column\ColumnInterface;
use Yiisoft\Db\Schema\Data\JsonLazyArray;
use Yiisoft\Db\Schema\Data\LazyArrayInterface;

/**
 * @group pgsql
 */
final class ArrayExpressionBuilderTest extends TestCase
{
    use TestTrait;

    public static function buildProvider(): array
    {
        return [
            [null, null, 'NULL', []],
            [[], null, 'ARRAY[]', []],
            [[1, 2, 3], null, 'ARRAY[:qp0,:qp1,:qp2]', [':qp0' => 1, ':qp1' => 2, ':qp2' => 3]],
            [
                new ArrayIterator(['a', 'b', 'c']),
                'varchar',
                'ARRAY[:qp0,:qp1,:qp2]::varchar[]',
                [':qp0' => 'a', ':qp1' => 'b', ':qp2' => 'c'],
            ],
            [
                new LazyArray('{1,2,3}'),
                'int[]',
                ':qp0::int[]',
                [':qp0' => new Param('{1,2,3}', DataType::STRING)],
            ],
            [
                new \Yiisoft\Db\Schema\Data\LazyArray('[1,2,3]'),
                ColumnBuilder::integer(),
                'ARRAY[:qp0,:qp1,:qp2]::integer[]',
                [':qp0' => 1, ':qp1' => 2, ':qp2' => 3],
            ],
            [
                new StructuredLazyArray('(1,2,3)'),
                'int',
                'ARRAY[:qp0,:qp1,:qp2]::int[]',
                [':qp0' => 1, ':qp1' => 2, ':qp2' => 3],
            ],
            [
                new JsonLazyArray('[1,2,3]'),
                ColumnBuilder::array(ColumnBuilder::integer()),
                'ARRAY[:qp0,:qp1,:qp2]::integer[]',
                [':qp0' => 1, ':qp1' => 2, ':qp2' => 3],
            ],
            [[new Expression('now()')], null, 'ARRAY[now()]', []],
            [
                [new JsonExpression(['a' => null, 'b' => 123, 'c' => [4, 5]]), new JsonExpression([true])],
                null,
                'ARRAY[:qp0,:qp1]',
                [
                    ':qp0' => new Param('{"a":null,"b":123,"c":[4,5]}', DataType::STRING),
                    ':qp1' => new Param('[true]', DataType::STRING),
                ],
            ],
            [
                [new JsonExpression(['a' => null, 'b' => 123, 'c' => [4, 5]]), new JsonExpression([true])],
                'jsonb',
                'ARRAY[:qp0,:qp1]::jsonb[]',
                [
                    ':qp0' => new Param('{"a":null,"b":123,"c":[4,5]}', DataType::STRING),
                    ':qp1' => new Param('[true]', DataType::STRING),
                ],
            ],
            [
                [
                    null,
                    new StructuredExpression(['value' => 11.11, 'currency_code' => 'USD']),
                    new StructuredExpression(['value' => null, 'currency_code' => null]),
                ],
                null,
                'ARRAY[:qp0,ROW(:qp1,:qp2),ROW(:qp3,:qp4)]',
                [':qp0' => null, ':qp1' => 11.11, ':qp2' => 'USD', ':qp3' => null, ':qp4' => null],
            ],
            [
                (new Query(self::getDb()))->select('id')->from('users')->where(['active' => 1]),
                null,
                'ARRAY(SELECT "id" FROM "users" WHERE "active"=:qp0)',
                [':qp0' => 1],
            ],
            [
                [(new Query(self::getDb()))->select('id')->from('users')->where(['active' => 1])],
                'integer[][]',
                'ARRAY[ARRAY(SELECT "id" FROM "users" WHERE "active"=:qp0)::integer[]]::integer[][]',
                [':qp0' => 1],
            ],
            [
                [[[true], [false, null]], [['t', 'f'], null], null],
                'bool[][][]',
                'ARRAY[ARRAY[ARRAY[:qp0]::bool[],ARRAY[:qp1,:qp2]::bool[]]::bool[][],ARRAY[ARRAY[:qp3,:qp4]::bool[],NULL]::bool[][],NULL]::bool[][][]',
                [
                    ':qp0' => true,
                    ':qp1' => false,
                    ':qp2' => null,
                    ':qp3' => 't',
                    ':qp4' => 'f',
                ],
            ],
            [
                ['a' => '1', 'b' => null],
                ColumnType::STRING,
                'ARRAY[:qp0,:qp1]::varchar(255)[]',
                [':qp0' => '1', ':qp1' => null],
            ],
            [
                '{1,2,3}',
                'string[]',
                ':qp0::varchar(255)[]',
                [':qp0' => new Param('{1,2,3}', DataType::STRING)],
            ],
            [
                [[1, null], null],
                'int[][]',
                'ARRAY[ARRAY[:qp0,:qp1]::int[],NULL]::int[][]',
                [':qp0' => '1', ':qp1' => null],
            ],
        ];
    }

    #[DataProvider('buildProvider')]
    public function testBuild(
        iterable|LazyArrayInterface|Query|string|null $value,
        ColumnInterface|string|null $type,
        string $expected,
        array $expectedParams
    ): void {
        $db = $this->getConnection();
        $qb = $db->getQueryBuilder();

        $params = [];
        $builder = new ArrayExpressionBuilder($qb);
        $expression = new ArrayExpression($value, $type);

        $this->assertSame($expected, $builder->build($expression, $params));
        $this->assertEquals($expectedParams, $params);
    }
}
