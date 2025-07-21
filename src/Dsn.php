<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql;

use Yiisoft\Db\Connection\AbstractDsn;

/**
 * Represents a Data Source Name (DSN) for a PostgreSQL Server that's used to configure a {@see Driver} instance.
 *
 * To get DSN in string format, use the `(string)` type casting operator.
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
        string $databaseName = 'postgres',
        string $port = '5432',
        array $options = [],
    ) {
        if ($databaseName === '') {
            $databaseName = 'postgres';
        }

        parent::__construct($driver, $host, $databaseName, $port, $options);
    }
}
