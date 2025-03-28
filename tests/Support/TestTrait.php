<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests\Support;

use Yiisoft\Db\Driver\Pdo\PdoConnectionInterface;
use Yiisoft\Db\Driver\Pdo\PdoDriverInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Pgsql\Connection;
use Yiisoft\Db\Pgsql\Driver;
use Yiisoft\Db\Pgsql\Dsn;
use Yiisoft\Db\Tests\Support\DbHelper;

trait TestTrait
{
    private string $dsn = '';
    private string $fixture = 'pgsql.sql';

    /**
     * @throws InvalidConfigException
     * @throws Exception
     */
    protected function getConnection(bool $fixture = false): PdoConnectionInterface
    {
        $db = new Connection($this->getDriver(), DbHelper::getSchemaCache());

        if ($fixture) {
            DbHelper::loadFixture($db, __DIR__ . "/Fixture/$this->fixture");
        }

        return $db;
    }

    protected static function getDb(): PdoConnectionInterface
    {
        $dsn = (new Dsn(
            host: self::getHost(),
            databaseName: self::getDatabaseName(),
            port: self::getPort(),
        ))->asString();
        $driver = new Driver($dsn, self::getUsername(), self::getPassword());
        $driver->charset('utf8');

        return new Connection($driver, DbHelper::getSchemaCache());
    }

    protected function getDsn(): string
    {
        if ($this->dsn === '') {
            $this->dsn = (new Dsn(
                host: self::getHost(),
                databaseName: self::getDatabaseName(),
                port: self::getPort(),
            ))->asString();
        }

        return $this->dsn;
    }

    protected function getDriverName(): string
    {
        return 'pgsql';
    }

    protected function setDsn(string $dsn): void
    {
        $this->dsn = $dsn;
    }

    protected function setFixture(string $fixture): void
    {
        $this->fixture = $fixture;
    }

    public static function setUpBeforeClass(): void
    {
        $db = self::getDb();

        DbHelper::loadFixture($db, __DIR__ . '/Fixture/pgsql.sql');

        $db->close();
    }

    protected function getDriver(): PdoDriverInterface
    {
        $driver = new Driver($this->getDsn(), self::getUsername(), self::getPassword());
        $driver->charset('utf8');

        return $driver;
    }

    private static function getDatabaseName(): string
    {
        return getenv('YII_PGSQL_DATABASE') ?: 'yiitest';
    }

    private static function getHost(): string
    {
        return getenv('YII_PGSQL_HOST') ?: '127.0.0.1';
    }

    private static function getPort(): string
    {
        return getenv('YII_PGSQL_PORT') ?: '5432';
    }

    private static function getUsername(): string
    {
        return getenv('YII_PGSQL_USER') ?: 'root';
    }

    private static function getPassword(): string
    {
        return getenv('YII_PGSQL_PASSWORD') ?: 'root';
    }
}
