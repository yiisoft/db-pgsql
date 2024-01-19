<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql;

use InvalidArgumentException;
use LogicException;
use Yiisoft\Db\Driver\Pdo\AbstractPdoCommand;

use function sprintf;

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

    /**
     * @see {https://www.postgresql.org/docs/current/sql-refreshmaterializedview.html}
     *
     * @param string $viewName
     * @param bool|null $concurrently Add [ CONCURRENTLY ] to refresh command
     * @param bool|null $withData Add [ WITH [ NO ] DATA ] to refresh command
     * @return bool
     * @throws \Throwable
     * @throws \Yiisoft\Db\Exception\Exception
     * @throws \Yiisoft\Db\Exception\InvalidConfigException
     */
    public function refreshMaterializedView(string $viewName, ?bool $concurrently = null, ?bool $withData = null): bool
    {
        if ($concurrently || ($concurrently === null || $withData === null)) {

            $tableSchema = $this->db->getTableSchema($viewName);

            if ($tableSchema) {
                $hasUnique = count($this->db->getSchema()->findUniqueIndexes($tableSchema)) > 0;
            } else {
                throw new InvalidArgumentException(
                    sprintf('"%s" not found in DB', $viewName)
                );
            }

            if ($concurrently && !$hasUnique) {
                throw new LogicException('CONCURRENTLY refresh is not allowed without unique index.');
            }

            $concurrently = $hasUnique;
        }

        $sql = 'REFRESH MATERIALIZED VIEW';

        if ($concurrently) {

            if ($withData === false) {
                throw new LogicException('CONCURRENTLY and WITH NO DATA may not be specified together.');
            }

            $sql .= ' CONCURRENTLY';
        }

        $sql .= ' ' . $this->db->getQuoter()->quoteTableName($viewName);

        if (is_bool($withData)) {
            $sql .= ' WITH ' . ($withData ? 'DATA' : 'NO DATA');
        }

        return $this->setSql($sql)->execute() === 0;
    }
}
