<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use Exception;
use PHPUnit\Framework\TestCase as AbstractTestCase;
use Yiisoft\Db\Pgsql\PDO\PDODriver;
use Yiisoft\Db\Pgsql\PDO\ConnectionPDOPgsql;
use Yiisoft\Db\TestSupport\TestTrait;

class TestCase extends AbstractTestCase
{
    use TestTrait;

    protected string $drivername = 'pgsql';
    protected string $dsn = 'pgsql:host=127.0.0.1;dbname=yiitest;port=5432';
    protected string $username = 'root';
    protected string $password = 'root';
    protected string $charset = 'UTF8';
    protected array $dataProvider;
    protected array $expectedSchemas = ['public'];
    protected string $likeEscapeCharSql = '';
    protected array $likeParameterReplacements = [];
    protected ?ConnectionPDOPgsql $db = null;

    /**
     * @param bool $reset whether to clean up the test database.
     *
     * @return ConnectionPDOPgsql
     */
    protected function getConnection(
        $reset = false,
        ?string $dsn = null,
        string $fixture = __DIR__ . '/Fixture/postgres.sql'
    ): ConnectionPDOPgsql {
        $pdoDriver = new PDODriver($dsn ?? $this->dsn, $this->username, $this->password);
        $this->db = new ConnectionPDOPgsql($pdoDriver, $this->createQueryCache(), $this->createSchemaCache());
        $this->db->setLogger($this->createLogger());
        $this->db->setProfiler($this->createProfiler());

        if ($reset === false) {
            return $this->db;
        }

        try {
            $this->prepareDatabase($this->db, $fixture);
        } catch (Exception $e) {
            $this->markTestSkipped('Something wrong when preparing database: ' . $e->getMessage());
        }

        return $this->db;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->db?->close();
        unset(
            $this->cache,
            $this->db,
            $this->logger,
            $this->queryCache,
            $this->schemaCache,
            $this->profiler
        );
    }
}
