<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests\Support;

use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Pgsql\Connection;
use Yiisoft\Db\Pgsql\Driver;
use Yiisoft\Db\Pgsql\Dsn;
use Yiisoft\Test\Support\SimpleCache\MemorySimpleCache;

trait IntegrationTestTrait
{
    use BaseTestTrait;

    protected function createConnection(): Connection
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

        $schemaCache = new SchemaCache(
            new MemorySimpleCache(),
        );

        return new Connection($driver, $schemaCache);
    }

    protected function getDefaultFixture(): string
    {
        return __DIR__ . '/Fixture/pgsql.sql';
    }
}
