<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Expression\ArrayExpression;
use Yiisoft\Db\Pgsql\Builder\ArrayExpressionBuilder;
use Yiisoft\Db\Pgsql\Tests\Support\TestTrait;

/**
 * @group pgsql
 */
final class ArrayExpressionBuilderTest extends TestCase
{
    use TestTrait;

    public function testTypecastValue(): void
    {
        $db = $this->getConnection();
        $arrayExpressionBuilder = new ArrayExpressionBuilder($db->getQueryBuilder());

        // Test array of json expression without type indication
        $arrayExpression = new ArrayExpression([[',', 'null', true, 'false', 'f']]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Array elements of ArrayExpression `$expression` must be instances of `ExpressionInterface`'
            . ' or `$expression` must have `json` or `jsonb` type of the array elements.'
        );

        $arrayExpressionBuilder->build($arrayExpression);
    }
}
