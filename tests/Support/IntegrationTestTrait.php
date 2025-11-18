<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests\Support;

use Yiisoft\Db\Pgsql\Connection;
use Yiisoft\Db\Pgsql\Driver;
use Yiisoft\Db\Pgsql\Dsn;
use Yiisoft\Db\Tests\Support\TestHelper;

trait IntegrationTestTrait
{
    protected function createConnection(): Connection
    {
        return new Connection(
            $this->createDriver(),
            TestHelper::createMemorySchemaCache(),
        );
    }

    protected function createDriver(): Driver
    {
        $host = getenv('YII_PGSQL_HOST') ?: '127.0.0.1';
        $databaseName = getenv('YII_PGSQL_DATABASE') ?: 'yiitest';
        $port = getenv('YII_PGSQL_PORT') ?: '5432';
        $username = getenv('YII_PGSQL_USER') ?: 'root';
        $password = getenv('YII_PGSQL_PASSWORD') ?: 'root';

        $dsn = new Dsn(
            host: $host,
            databaseName: $databaseName,
            port: $port,
        );

        $driver = new Driver($dsn, $username, $password);
        $driver->charset('utf8');

        return $driver;
    }

    protected function getDefaultFixture(): string
    {
        return __DIR__ . '/Fixture/pgsql.sql';
    }

    protected function ensureMinPostgreSqlVersion(string $version): void
    {
        $currentVersion = $this->getSharedConnection()->getServerInfo()->getVersion();
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
