<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql;

use PDO;
use Yiisoft\Db\Driver\PDO\CommandPDOInterface;
use Yiisoft\Db\Driver\PDO\ConnectionPDO as AbstractConnectionPDO;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;
use Yiisoft\Db\Schema\Quoter;
use Yiisoft\Db\Schema\QuoterInterface;
use Yiisoft\Db\Schema\SchemaInterface;
use Yiisoft\Db\Transaction\TransactionInterface;

/**
 * Database connection class prefilled for PgSQL Server.
 * The class Connection represents a connection to a database via [PDO](https://secure.php.net/manual/en/book.pdo.php).
 */
final class ConnectionPDO extends AbstractConnectionPDO
{
    public function createCommand(?string $sql = null, array $params = []): CommandPDOInterface
    {
        $command = new CommandPDO($this, $this->queryCache);

        if ($sql !== null) {
            $command->setSql($sql);
        }

        if ($this->logger !== null) {
            $command->setLogger($this->logger);
        }

        if ($this->profiler !== null) {
            $command->setProfiler($this->profiler);
        }

        return $command->bindValues($params);
    }

    public function createTransaction(): TransactionInterface
    {
        return new TransactionPDO($this);
    }

    public function getLastInsertID(?string $sequenceName = null): string
    {
        if ($sequenceName === null) {
            throw new InvalidArgumentException('PgSQL not support lastInsertId without sequence name');
        }

        return parent::getLastInsertID($sequenceName);
    }

    public function getName(): string
    {
        return $this->getDriver()->getName();
    }

    /**
     * @throws Exception|InvalidConfigException
     */
    public function getQueryBuilder(): QueryBuilderInterface
    {
        if ($this->queryBuilder === null) {
            $this->queryBuilder = new QueryBuilder(
                $this->getQuoter(),
                $this->getSchema(),
            );
        }

        return $this->queryBuilder;
    }

    public function getQuoter(): QuoterInterface
    {
        if ($this->quoter === null) {
            $this->quoter = new Quoter('"', '"', $this->getTablePrefix());
        }

        return $this->quoter;
    }

    public function getSchema(): SchemaInterface
    {
        if ($this->schema === null) {
            $this->schema = new Schema($this, $this->schemaCache);
        }

        return $this->schema;
    }

    /**
     * Initializes the DB connection.
     *
     * This method is invoked right after the DB connection is established.
     *
     * The default implementation turns on `PDO::ATTR_EMULATE_PREPARES`.
     *
     * if {@see emulatePrepare} is true, and sets the database {@see charset} if it is not empty.
     *
     * It then triggers an {@see EVENT_AFTER_OPEN} event.
     */
    protected function initConnection(): void
    {
        if ($this->getEmulatePrepare() !== null) {
            $this->driver->attributes([PDO::ATTR_EMULATE_PREPARES => $this->getEmulatePrepare()]);
        }

        $this->pdo = $this->driver->createConnection();
    }
}
