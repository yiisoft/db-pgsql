<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use PHPUnit\Framework\TestCase as AbstractTestCase;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Pgsql\Connection;
use Yiisoft\Db\TestUtility\TestTrait;

class TestCase extends AbstractTestCase
{
    use TestTrait;

    protected const DB_DSN = 'pgsql:host=127.0.0.1;dbname=yiitest;port=5432';
    protected const DB_FIXTURES_PATH = __DIR__ . '/Fixture/postgres.sql';
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

    protected function createConnection(string $dsn = null): ?ConnectionInterface
    {
        $db = null;

        if ($dsn !== null) {
            $db = new Connection($dsn, $this->createQueryCache(), $this->createSchemaCache());
            $db->setLogger($this->createLogger());
            $db->setProfiler($this->createProfiler());
            $db->setUsername('root');
            $db->setPassword('root');
            $db->setCharset('UTF8');
        }

        return $db;
    }

    /**
     * Adjust dbms specific escaping.
     *
     * @param array|string $sql
     *
     * @return array|string
     */
    protected function replaceQuotes($sql)
    {
        return str_replace(['\\[', '\\]'], ['[', ']'], preg_replace('/(\[\[)|((?<!(\[))\]\])/', '"', $sql));
    }
}
