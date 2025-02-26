<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use ArrayIterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Command\Param;
use Yiisoft\Db\Constant\DataType;
use Yiisoft\Db\Expression\JsonExpression;
use Yiisoft\Db\Pgsql\Builder\JsonExpressionBuilder;
use Yiisoft\Db\Pgsql\Column\IntegerColumn;
use Yiisoft\Db\Pgsql\Data\LazyArray;
use Yiisoft\Db\Pgsql\Data\StructuredLazyArray;
use Yiisoft\Db\Pgsql\Tests\Support\TestTrait;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Schema\Data\JsonLazyArray;

/**
 * @group pgsql
 */
final class JsonExpressionBuilderTest extends TestCase
{
    use TestTrait;

    public static function buildProvider(): array
    {
        return [
            ['', '""'],
            [1, '1'],
            [true, 'true'],
            [[1, 2, 3], '[1,2,3]'],
            [new ArrayIterator(['a', 'b', 'c']), '["a","b","c"]'],
            [new LazyArray('{1,2,3}'), '["1","2","3"]'],
            [new LazyArray('{1,2,3}', new IntegerColumn()), '[1,2,3]'],
            [new LazyArray('{{1,2,3},}', new IntegerColumn(), 2), '[[1,2,3],null]'],
            [new \Yiisoft\Db\Schema\Data\LazyArray('[1,2,3]'), '[1,2,3]'],
            [new JsonLazyArray('[1,2,3]'), '[1,2,3]'],
            [new StructuredLazyArray('(5,USD)'), '["5","USD"]'],
            [new \Yiisoft\Db\Schema\Data\StructuredLazyArray('[5,"USD"]'), '[5,"USD"]'],
            [['a' => 1, 'b' => null, 'c' => ['d' => 'e']], '{"a":1,"b":null,"c":{"d":"e"}}'],
            ['[1,2,3]', '[1,2,3]'],
            ['{"a":1,"b":null,"c":{"d":"e"}}', '{"a":1,"b":null,"c":{"d":"e"}}'],
        ];
    }

    #[DataProvider('buildProvider')]
    public function testBuild(mixed $value, string $expected): void
    {
        $db = $this->getConnection();
        $qb = $db->getQueryBuilder();

        $params = [];
        $builder = new JsonExpressionBuilder($qb);
        $expression = new JsonExpression($value);

        $this->assertSame(':qp0', $builder->build($expression, $params));
        $this->assertEquals([':qp0' => new Param($expected, DataType::STRING)], $params);
    }

    public function testBuildNull(): void
    {
        $db = $this->getConnection();
        $qb = $db->getQueryBuilder();

        $params = [];
        $builder = new JsonExpressionBuilder($qb);
        $expression = new JsonExpression(null);

        $this->assertSame('NULL', $builder->build($expression, $params));
        $this->assertSame([], $params);
    }

    public function testBuildQueryExpression(): void
    {
        $db = $this->getConnection();
        $qb = $db->getQueryBuilder();

        $params = [];
        $builder = new JsonExpressionBuilder($qb);
        $expression = new JsonExpression((new Query($db))->select('json_field')->from('json_table'));

        $this->assertSame('(SELECT "json_field" FROM "json_table")', $builder->build($expression, $params));
        $this->assertSame([], $params);

        $expression = new JsonExpression((new Query($db))->select('json_field')->from('json_table'), 'jsonb');

        $this->assertSame('(SELECT "json_field" FROM "json_table")::jsonb', $builder->build($expression, $params));
        $this->assertSame([], $params);
    }

    public function testBuildWithType(): void
    {
        $db = $this->getConnection();
        $qb = $db->getQueryBuilder();

        $params = [];
        $builder = new JsonExpressionBuilder($qb);
        $expression = new JsonExpression([1, 2, 3], 'jsonb');

        $this->assertSame(':qp0::jsonb', $builder->build($expression, $params));
        $this->assertEquals([':qp0' => new Param('[1,2,3]', DataType::STRING)], $params);
    }
}
