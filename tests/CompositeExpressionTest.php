<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Pgsql\Expression\CompositeExpression;
use Yiisoft\Db\Pgsql\Tests\Support\TestTrait;

/**
 * @group pgsql
 */
final class CompositeExpressionTest extends TestCase
{
    use TestTrait;

    public function testGetNormalizedValue(): void
    {
        $db = $this->getConnection(true);
        $schema = $db->getSchema();
        $tableSchema = $schema->getTableSchema('test_composite_type');

        $columns = $tableSchema->getColumn('price_default')->getColumns();
        $this->assertNotNull($columns);

        $compositeExpression = new CompositeExpression(['currency_code' => 'USD', 'value' => 10.0], 'currency_money_composite', $columns);
        $this->assertSame(['value' => 10.0, 'currency_code' => 'USD'], $compositeExpression->getNormalizedValue());

        $compositeExpression = new CompositeExpression(['value' => 10.0], 'currency_money_composite', $columns);
        $this->assertSame(['value' => 10.0, 'currency_code' => 'USD'], $compositeExpression->getNormalizedValue());

        $compositeExpression = new CompositeExpression([10.0], 'currency_money_composite', $columns);
        $this->assertSame([10.0, 'USD'], $compositeExpression->getNormalizedValue());

        $compositeExpression = new CompositeExpression([], 'currency_money_composite', $columns);
        $this->assertSame(['value' => 5.0, 'currency_code' => 'USD'], $compositeExpression->getNormalizedValue());
    }
}
