<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use Yiisoft\Db\Pgsql\Column\ColumnDefinitionParser;
use Yiisoft\Db\Pgsql\Tests\Provider\ColumnDefinitionParserProvider;
use Yiisoft\Db\Tests\Common\CommonColumnDefinitionParserTest;

/**
 * @group pgsql
 */
final class ColumnDefinitionParserTest extends CommonColumnDefinitionParserTest
{
    #[DataProviderExternal(ColumnDefinitionParserProvider::class, 'parse')]
    public function testParse(string $definition, array $expected): void
    {
        parent::testParse($definition, $expected);
    }

    protected function createColumnDefinitionParser(): ColumnDefinitionParser
    {
        return new ColumnDefinitionParser();
    }
}
