<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests\Support;

use Yiisoft\Db\Driver\PDO\ConnectionPDOInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Pgsql\ConnectionPDO;
use Yiisoft\Db\Pgsql\Dsn;
use Yiisoft\Db\Pgsql\PDODriver;
use Yiisoft\Db\Tests\Support\DbHelper;

trait TestTrait
{
    private string $dsn = '';
    private string $fixture = 'pgsql.sql';

    /**
     * @throws InvalidConfigException
     * @throws Exception
     */
    protected function getConnection(bool $fixture = false): ConnectionPDOInterface
    {
        $pdoDriver = new PDODriver($this->getDsn(), 'root', 'root');
        $pdoDriver->charset('utf8');
        $db = new ConnectionPDO(
            $pdoDriver,
            DbHelper::getSchemaCache(),
        );

        if ($fixture) {
            DbHelper::loadFixture($db, __DIR__ . "/Fixture/$this->fixture");
        }

        return $db;
    }

    protected static function getDb(): ConnectionPDOInterface
    {
        $dsn = (new Dsn('pgsql', '127.0.0.1', 'yiitest', '5432'))->asString();

        return new ConnectionPDO(
            new PDODriver($dsn, 'root', 'root'),
            DbHelper::getSchemaCache(),
        );
    }

    protected function getDsn(): string
    {
        if ($this->dsn === '') {
            $this->dsn = (new Dsn('pgsql', '127.0.0.1', 'yiitest', '5432'))->asString();
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
}
