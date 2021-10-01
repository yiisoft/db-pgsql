<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use PHPUnit\Framework\TestCase as AbstractTestCase;
use Yiisoft\Db\Pgsql\Connection;
use Yiisoft\Db\TestUtility\TestTrait;

class TestCase extends AbstractTestCase
{
    use TestTrait;

    protected const DB_CONNECTION_CLASS = \Yiisoft\Db\Pgsql\Connection::class;
    protected const DB_DRIVERNAME = 'pgsql';
    protected const DB_DSN = 'pgsql:host=127.0.0.1;dbname=yiitest;port=5432';
    protected const DB_FIXTURES_PATH = __DIR__ . '/Fixture/postgres.sql';
    protected const DB_USERNAME = 'root';
    protected const DB_PASSWORD = 'root';
    protected const DB_CHARSET = 'UTF8';
    protected array $dataProvider;
    protected array $expectedSchemas = ['public'];
    protected string $likeEscapeCharSql = '';
    protected array $likeParameterReplacements = [];
    protected Connection $connection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->connection = $this->createConnection(self::DB_DSN);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->connection->close();
        unset(
            $this->cache,
            $this->connection,
            $this->logger,
            $this->queryCache,
            $this->schemaCache,
            $this->profiler
        );
    }
}
