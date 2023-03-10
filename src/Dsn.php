<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql;

use Yiisoft\Db\Connection\AbstractDsn;

/**
 * Implement a Data Source Name (DSN) for a PostgreSQL Server.
 *
 * @link https://www.php.net/manual/en/ref.pdo-pgsql.connection.php
 */
final class Dsn extends AbstractDsn
{
    /**
     * @psalm-param string[] $options
     */
    public function __construct(
        string $driver,
        string $host,
        string $databaseName,
        string $port = '5432',
        array $options = []
    ) {
        parent::__construct($driver, $host, $databaseName, $port, $options);
    }
}
