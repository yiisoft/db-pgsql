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
            [null, null, 'NULL', []],
            [[null, null], null, 'ROW(:qp0,:qp1)', [':qp0' => null, ':qp1' => null]],
            [['5', 'USD'], null, 'ROW(:qp0,:qp1)', [':qp0' => '5', ':qp1' => 'USD']],
            [
                new ArrayIterator(['5', 'USD']),
                $column,
                'ROW(:qp0,:qp1)::currency_money',
                [':qp0' => 5, ':qp1' => 'USD'],
            ],
            [
                new StructuredLazyArray('(5,USD)'),
                $column,
                ':qp0::currency_money',
                [':qp0' => new Param('(5,USD)', DataType::STRING)],
            ],
            [
                new \Yiisoft\Db\Schema\Data\StructuredLazyArray('["5","USD"]'),
                $column,
                'ROW(:qp0,:qp1)::currency_money',
                [':qp0' => 5, ':qp1' => 'USD'],
            ],
            [
                new LazyArray('{5,USD}'),
                $column,
                'ROW(:qp0,:qp1)::currency_money',
                [':qp0' => 5, ':qp1' => 'USD'],
            ],
            [
                new JsonLazyArray('["5","USD"]'),
                $column,
                'ROW(:qp0,:qp1)::currency_money',
                [':qp0' => 5, ':qp1' => 'USD'],
            ],
            [
                (new Query(self::getDb()))->select('price')->from('product')->where(['id' => 1]),
                null,
                '(SELECT "price" FROM "product" WHERE "id"=:qp0)',
                [':qp0' => 1],
            ],
            [
                (new Query(self::getDb()))->select('price')->from('product')->where(['id' => 1]),
                'currency_money',
                '(SELECT "price" FROM "product" WHERE "id"=:qp0)::currency_money',
                [':qp0' => 1],
            ],
            [
                ['value' => '5', 'currency_code' => 'USD'],
                $column,
                'ROW(:qp0,:qp1)::currency_money',
                [':qp0' => 5, ':qp1' => 'USD'],
            ],
            [
                ['currency_code' => 'USD', 'value' => '5'],
                $column,
                'ROW(:qp0,:qp1)::currency_money',
                [':qp0' => 5, ':qp1' => 'USD'],
            ],
            [['value' => '5'], $column, 'ROW(:qp0,:qp1)::currency_money', [':qp0' => 5, ':qp1' => 'USD']],
            [['value' => '5'], null, 'ROW(:qp0)', [':qp0' => '5']],
            [
                ['value' => '5', 'currency_code' => 'USD', 'extra' => 'value'],
                $column,
                'ROW(:qp0,:qp1)::currency_money',
                [':qp0' => 5, ':qp1' => 'USD'],
            ],
            [(object) ['value' => '5', 'currency_code' => 'USD'],
                'currency_money',
                'ROW(:qp0,:qp1)::currency_money',
                [':qp0' => 5, ':qp1' => 'USD'],
            ],
            ['(5,USD)', null, ':qp0', [':qp0' => new Param('(5,USD)', DataType::STRING)]],
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
        $this->assertEquals($expectedParams, $params);
    }
}
