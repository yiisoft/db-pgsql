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
     * @psalm-param array<string,string> $options
     */
    public function __construct(
        string $driver = 'pgsql',
        string $host = '127.0.0.1',
        string|null $databaseName = 'postgres',
        string $port = '5432',
        array $options = []
    ) {
        if (empty($databaseName)) {
            $databaseName = 'postgres';
        }

        parent::__construct($driver, $host, $databaseName, $port, $options);
    }
}
