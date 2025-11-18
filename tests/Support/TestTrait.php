<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests\Support;

use Yiisoft\Db\Pgsql\Connection;
use Yiisoft\Db\Pgsql\Driver;
use Yiisoft\Db\Pgsql\Tests\TestConnection;
use Yiisoft\Db\Tests\Support\DbHelper;

use function preg_replace;
use function str_replace;

trait TestTrait
{
    private string $dsn = '';
    private string $fixture = 'pgsql.sql';

    public static function setUpBeforeClass(): void
    {
        $db = self::getDb();

        DbHelper::loadFixture($db, __DIR__ . '/Fixture/pgsql.sql');

        $db->close();
    }

    protected function getConnection(bool $fixture = false): Connection
    {
        $db = TestConnection::create($this->getDsn());

        if ($fixture) {
            DbHelper::loadFixture($db, __DIR__ . "/Fixture/$this->fixture");
        }

        return $db;
    }

    protected static function getDb(): Connection
    {
        return TestConnection::create();
    }

    protected function getDsn(): string
    {
        if ($this->dsn === '') {
            $this->dsn = TestConnection::dsn();
        }

        return $this->dsn;
    }

    protected static function getDriverName(): string
    {
        return 'pgsql';
    }

    protected static function replaceQuotes(string $sql): string
    {
        return str_replace(['\\[', '\\]'], ['[', ']'], preg_replace('/(\[\[)|((?<!(\[))]])/', '"', $sql));
    }

    protected function setDsn(string $dsn): void
    {
        $this->dsn = $dsn;
    }

    protected function setFixture(string $fixture): void
    {
        $this->fixture = $fixture;
    }

    protected function getDriver(): Driver
    {
        return TestConnection::createDriver();
    }

    protected function ensureMinPostgreSqlVersion(string $version): void
    {
        $currentVersion = TestConnection::getShared()->getServerInfo()->getVersion();
        if (version_compare($currentVersion, $version, '<')) {
            $this->markTestSkipped(
                "This test requires at least PostgreSQL version $version. Current version is $currentVersion.",
            );
        }
    }
}
