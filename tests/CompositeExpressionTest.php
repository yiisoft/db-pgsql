<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Pgsql\Composite\CompositeExpression;
use Yiisoft\Db\Pgsql\Tests\Support\TestTrait;

/**
 * @group pgsql
 */
final class CompositeExpressionTest extends TestCase
{
    use TestTrait;

    /** @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\CompositeTypeProvider::normolizedValues */
    public function testGetNormalizedValue(mixed $value, mixed $expected, array $columns): void
    {
        $compositeExpression = new CompositeExpression($value, 'currency_money_composite', $columns);
        $this->assertSame($expected, $compositeExpression->getNormalizedValue());
    }
}
