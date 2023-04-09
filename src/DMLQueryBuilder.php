<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql;

use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\QueryBuilder\AbstractDMLQueryBuilder;

use function implode;
use function reset;

/**
 * Implements a DML (Data Manipulation Language) SQL statements for PostgreSQL Server.
 */
final class DMLQueryBuilder extends AbstractDMLQueryBuilder
{
    public function insertWithReturningPks(string $table, QueryInterface|array $columns, array &$params = []): string
    {
        $sql = $this->insert($table, $columns, $params);

        $tableSchema = $this->schema->getTableSchema($table);

        $returnColumns = [];
        if ($tableSchema !== null) {
            $returnColumns = $tableSchema->getPrimaryKey();
        }

        if (!empty($returnColumns)) {
            $returning = [];
            foreach ($returnColumns as $name) {
                $returning[] = $this->quoter->quoteColumnName($name);
            }
            $sql .= ' RETURNING ' . implode(', ', $returning);
        }

        return $sql;
    }

    public function resetSequence(string $table, int|string $value = null): string
    {
        $tableSchema = $this->schema->getTableSchema($table);

        if ($tableSchema !== null && ($sequence = $tableSchema->getSequenceName()) !== null) {
            /**
             * @link https://www.postgresql.org/docs/8.1/static/functions-sequence.html
             */
            $sequence = $this->quoter->quoteTableName($sequence);
            $table = $this->quoter->quoteTableName($table);

            if ($value === null) {
                $pk = $tableSchema->getPrimaryKey();
                $key = $this->quoter->quoteColumnName(reset($pk));
                $value = "(SELECT COALESCE(MAX($key),0) FROM $table)+1";
            }

            return "SELECT SETVAL('$sequence',$value,false)";
        }

        if ($tableSchema === null) {
            throw new InvalidArgumentException("Table not found: '$table'.");
        }

        throw new InvalidArgumentException("There is not sequence associated with table '$table'.");
    }

    public function upsert(
        string $table,
        QueryInterface|array $insertColumns,
        $updateColumns,
        array &$params = []
    ): string {
        $insertSql = $this->insert($table, $insertColumns, $params);

        /** @psalm-var array $uniqueNames */
        [$uniqueNames, , $updateNames] = $this->prepareUpsertColumns(
            $table,
            $insertColumns,
            $updateColumns,
        );

        if (empty($uniqueNames)) {
            return $insertSql;
        }

        if ($updateNames === []) {
            /** there are no columns to update */
            $updateColumns = false;
        }

        if ($updateColumns === false) {
            return "$insertSql ON CONFLICT DO NOTHING";
        }

        if ($updateColumns === true) {
            $updateColumns = [];

            /** @psalm-var string $name */
            foreach ($updateNames as $name) {
                $updateColumns[$name] = new Expression(
                    'EXCLUDED.' . $this->quoter->quoteColumnName($name)
                );
            }
        }

        /**
         * @psalm-var array $updateColumns
         * @psalm-var string[] $uniqueNames
         * @psalm-var string[] $updates
         */
        [$updates, $params] = $this->prepareUpdateSets($table, $updateColumns, $params);

        return $insertSql
            . ' ON CONFLICT (' . implode(', ', $uniqueNames) . ') DO UPDATE SET ' . implode(', ', $updates);
    }
}
