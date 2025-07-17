<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use ArrayIterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Command\Param;
use Yiisoft\Db\Constant\DataType;
use Yiisoft\Db\Expression\StructuredExpression;
use Yiisoft\Db\Pgsql\Builder\StructuredExpressionBuilder;
use Yiisoft\Db\Pgsql\Column\ColumnBuilder;
use Yiisoft\Db\Pgsql\Data\LazyArray;
use Yiisoft\Db\Pgsql\Data\StructuredLazyArray;
use Yiisoft\Db\Pgsql\Tests\Support\TestTrait;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Schema\Column\AbstractStructuredColumn;
use Yiisoft\Db\Schema\Data\JsonLazyArray;
use Yiisoft\Db\Tests\Support\Assert;

/**
 * @group pgsql
 */
final class StructuredExpressionBuilderTest extends TestCase
{
    use TestTrait;

    public static function buildProvider(): array
    {
        $column = ColumnBuilder::structured('currency_money', [
            'value' => ColumnBuilder::integer(),
            'currency_code' => ColumnBuilder::string()->defaultValue('USD'),
        ]);

        return [
            'null' => [null, null, 'NULL', []],
            'nulls' => [[null, null], null, 'ROW(NULL,NULL)', []],
            'array w/o type' => [
                ['5', 'USD'],
                null,
                'ROW(:qp0,:qp1)',
                [
                    ':qp0' => new Param('5', DataType::STRING),
                    ':qp1' => new Param('USD', DataType::STRING),
                ],
            ],
            'ArrayIterator' => [
                new ArrayIterator(['5', 'USD']),
                $column,
                'ROW(5,:qp0)::currency_money',
                [':qp0' => new Param('USD', DataType::STRING)],
            ],
            'StructuredLazyArray' => [
                new StructuredLazyArray('(5,USD)'),
                $column,
                ':qp0::currency_money',
                [':qp0' => new Param('(5,USD)', DataType::STRING)],
            ],
            'StructuredLazyArray external' => [
                new \Yiisoft\Db\Schema\Data\StructuredLazyArray('["5","USD"]'),
                $column,
                'ROW(5,:qp0)::currency_money',
                [':qp0' => new Param('USD', DataType::STRING)],
            ],
            'LazyArray' => [
                new LazyArray('{5,USD}'),
                $column,
                'ROW(5,:qp0)::currency_money',
                [':qp0' => new Param('USD', DataType::STRING)],
            ],
            'JsonLazyArray' => [
                new JsonLazyArray('["5","USD"]'),
                $column,
                'ROW(5,:qp0)::currency_money',
                [':qp0' => new Param('USD', DataType::STRING)],
            ],
            'Query w/o type' => [
                (new Query(self::getDb()))->select('price')->from('product')->where(['id' => 1]),
                null,
                '(SELECT "price" FROM "product" WHERE "id"=1)',
                [],
            ],
            'Query' => [
                (new Query(self::getDb()))->select('price')->from('product')->where(['id' => 1]),
                'currency_money',
                '(SELECT "price" FROM "product" WHERE "id"=1)::currency_money',
                [],
            ],
            'ordered array' => [
                ['value' => '5', 'currency_code' => 'USD'],
                $column,
                'ROW(5,:qp0)::currency_money',
                [':qp0' => new Param('USD', DataType::STRING)],
            ],
            'unordered array' => [
                ['currency_code' => 'USD', 'value' => '5'],
                $column,
                'ROW(5,:qp0)::currency_money',
                [':qp0' => new Param('USD', DataType::STRING)],
            ],
            'missing items' => [
                ['value' => '5'],
                $column,
                'ROW(5,:qp0)::currency_money',
                [':qp0' => new Param('USD', DataType::STRING)],
            ],
            'missing items w/o type' => [
                ['value' => '5'],
                null,
                'ROW(:qp0)',
                [':qp0' => new Param('5', DataType::STRING)],
            ],
            'extra items' => [
                ['value' => '5', 'currency_code' => 'USD', 'extra' => 'value'],
                $column,
                'ROW(5,:qp0)::currency_money',
                [':qp0' => new Param('USD', DataType::STRING)],
            ],
            'object' => [(object) ['value' => '5', 'currency_code' => 'USD'],
                'currency_money',
                'ROW(:qp0,:qp1)::currency_money',
                [
                    ':qp0' => new Param('5', DataType::STRING),
                    ':qp1' => new Param('USD', DataType::STRING),
                ],
            ],
            'string' => ['(5,USD)', null, ':qp0', [':qp0' => new Param('(5,USD)', DataType::STRING)]],
        ];
    }

    #[DataProvider('buildProvider')]
    public function testBuild(
        array|object|string|null $value,
        AbstractStructuredColumn|string|null $type,
        string $expected,
        array $expectedParams
    ): void {
        $db = $this->getConnection();
        $qb = $db->getQueryBuilder();

        $params = [];
        $builder = new StructuredExpressionBuilder($qb);
        $expression = new StructuredExpression($value, $type);

        $this->assertSame($expected, $builder->build($expression, $params));
        Assert::arraysEquals($expectedParams, $params);
    }
}
