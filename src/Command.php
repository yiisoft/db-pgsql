<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql;

use Exception;
use Throwable;
use Yiisoft\Db\Driver\Pdo\AbstractPdoCommand;
use Yiisoft\Db\Exception\ConvertException;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

/**
 * Implements a database command that can be executed with a PDO (PHP Data Object) database connection for PostgreSQL
 * Server.
 */
final class Command extends AbstractPdoCommand
{
    public function showDatabases(): array
    {
        $sql = <<<SQL
        SELECT datname FROM pg_database WHERE datistemplate = false AND datname NOT IN ('postgres', 'template0', 'template1')
        SQL;

        return $this->setSql($sql)->queryColumn();
    }
}
