<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use Yiisoft\Db\Pgsql\Connection;
use Yiisoft\Db\Pgsql\Driver;
use Yiisoft\Db\Pgsql\Dsn;
use Yiisoft\Db\Tests\Support\DbHelper;

final class TestConnection
{
    private static ?string $dsn = null;
    private static ?Connection $connection = null;

    public static function get(): Connection
    {
        $db = self::$connection ??= self::create();
        $db->getSchema()->refresh();
        return $db;
    }

    public static function dsn(): string
    {
        return self::$dsn ??= (string) new Dsn(
            host: self::host(),
            databaseName: self::databaseName(),
            port: self::port(),
        );
    }

    public static function create(?string $dsn = null): Connection
    {
        return new Connection(self::createDriver($dsn), DbHelper::getSchemaCache());
    }

    public static function createDriver(?string $dsn = null): Driver
    {
        $driver = new Driver($dsn ?? self::dsn(), self::username(), self::password());
        $driver->charset('utf8');
        return $driver;
    }

    public static function databaseName(): string
    {
        return getenv('YII_PGSQL_DATABASE') ?: 'yiitest';
    }

    private static function host(): string
    {
        return getenv('YII_PGSQL_HOST') ?: '127.0.0.1';
    }

    private static function port(): string
    {
        return getenv('YII_PGSQL_PORT') ?: '5432';
    }

    private static function username(): string
    {
        return getenv('YII_PGSQL_USER') ?: 'root';
    }

    private static function password(): string
    {
        return getenv('YII_PGSQL_PASSWORD') ?: 'root';
    }
}
