<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use Yiisoft\Db\Pgsql\Column\ColumnDefinitionParser;
use Yiisoft\Db\Tests\AbstractColumnDefinitionParserTest;

/**
 * @group pgsql
 */
final class ColumnDefinitionParserTest extends AbstractColumnDefinitionParserTest
{
    /**
     * @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\ColumnDefinitionParserProvider::parse
     */
    public function testParse(string $definition, array $expected): void
    {
        parent::testParse($definition, $expected);
    }

    protected function createColumnDefinitionParser(): ColumnDefinitionParser
    {
        return new ColumnDefinitionParser();
    }
}
