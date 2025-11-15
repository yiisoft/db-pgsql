<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use ArrayIterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Constant\DataType;
use Yiisoft\Db\Expression\Value\ArrayValue;
use Yiisoft\Db\Expression\Value\JsonValue;
use Yiisoft\Db\Expression\Value\Param;
use Yiisoft\Db\Pgsql\Builder\JsonValueBuilder;
use Yiisoft\Db\Pgsql\Column\IntegerColumn;
use Yiisoft\Db\Pgsql\Data\LazyArray;
use Yiisoft\Db\Pgsql\Data\StructuredLazyArray;
use Yiisoft\Db\Pgsql\Tests\Support\TestTrait;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Schema\Data\JsonLazyArray;

/**
 * @group pgsql
 */
final class JsonValueBuilderTest extends TestCase
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
        $builder = new JsonValueBuilder($qb);
        $expression = new JsonValue($value);

        $this->assertSame(':qp0', $builder->build($expression, $params));
        $this->assertEquals([':qp0' => new Param($expected, DataType::STRING)], $params);

        $db->close();
    }

    public function testBuildArrayValue(): void
    {
        $db = $this->getConnection();
        $qb = $db->getQueryBuilder();

        $params = [];
        $builder = new JsonValueBuilder($qb);
        $expression = new JsonValue(new ArrayValue([1,2,3]));

        $this->assertSame('array_to_json(ARRAY[1,2,3]::int[])', $builder->build($expression, $params));
        $this->assertSame([], $params);

        $params = [];
        $expression = new JsonValue(new ArrayValue([1,2,3]), 'jsonb');

        $this->assertSame('array_to_json(ARRAY[1,2,3]::int[])::jsonb', $builder->build($expression, $params));
        $this->assertSame([], $params);

        $db->close();
    }

    public function testBuildNull(): void
    {
        $db = $this->getConnection();
        $qb = $db->getQueryBuilder();

        $params = [];
        $builder = new JsonValueBuilder($qb);
        $expression = new JsonValue(null);

        $this->assertSame('NULL', $builder->build($expression, $params));
        $this->assertSame([], $params);

        $db->close();
    }

    public function testBuildQueryExpression(): void
    {
        $db = $this->getConnection();
        $qb = $db->getQueryBuilder();

        $params = [];
        $builder = new JsonValueBuilder($qb);
        $expression = new JsonValue((new Query($db))->select('json_field')->from('json_table'));

        $this->assertSame('(SELECT "json_field" FROM "json_table")', $builder->build($expression, $params));
        $this->assertSame([], $params);

        $expression = new JsonValue((new Query($db))->select('json_field')->from('json_table'), 'jsonb');

        $this->assertSame('(SELECT "json_field" FROM "json_table")::jsonb', $builder->build($expression, $params));
        $this->assertSame([], $params);

        $db->close();
    }

    public function testBuildWithType(): void
    {
        $db = $this->getConnection();
        $qb = $db->getQueryBuilder();

        $params = [];
        $builder = new JsonValueBuilder($qb);
        $expression = new JsonValue([1, 2, 3], 'jsonb');

        $this->assertSame(':qp0::jsonb', $builder->build($expression, $params));
        $this->assertEquals([':qp0' => new Param('[1,2,3]', DataType::STRING)], $params);

        $db->close();
    }
}
