<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests\Support;

use Yiisoft\Db\Pgsql\Connection;
use Yiisoft\Db\Tests\Support\TestHelper;

trait IntegrationTestTrait
{
    protected function createConnection(): Connection
    {
        return new Connection(
            TestConnection::createDriver(),
            TestHelper::createMemorySchemaCache(),
        );
    }

    protected function getDefaultFixture(): string
    {
        return __DIR__ . '/Fixture/pgsql.sql';
    }

    protected function ensureMinPostgreSqlVersion(string $version): void
    {
        $currentVersion = TestConnection::getServerVersion();
        if (version_compare($currentVersion, $version, '<')) {
            $this->markTestSkipped(
                "This test requires at least PostgreSQL version $version. Current version is $currentVersion.",
            );
        }
    }

    protected function replaceQuotes(string $sql): string
    {
        return str_replace(['\\[', '\\]'], ['[', ']'], preg_replace('/(\[\[)|((?<!(\[))]])/', '"', $sql));
    }
}
