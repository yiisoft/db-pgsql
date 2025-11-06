<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use Yiisoft\Db\Pgsql\SqlParser;
use Yiisoft\Db\Tests\AbstractSqlParserTest;

/**
 * @group pgsql
 */
final class SqlParserTest extends AbstractSqlParserTest
{
    /** @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\SqlParserProvider::getNextPlaceholder */
    public function testGetNextPlaceholder(string $sql, ?string $expectedPlaceholder, ?int $expectedPosition): void
    {
        parent::testGetNextPlaceholder($sql, $expectedPlaceholder, $expectedPosition);
    }

    protected function createSqlParser(string $sql): SqlParser
    {
        return new SqlParser($sql);
    }
}
