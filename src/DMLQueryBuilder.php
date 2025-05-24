<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql;

use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\QueryBuilder\AbstractDMLQueryBuilder;

use function array_map;
use function implode;
use function str_ends_with;
use function substr;

/**
 * Implements a DML (Data Manipulation Language) SQL statements for PostgreSQL Server.
 */
final class DMLQueryBuilder extends AbstractDMLQueryBuilder
{
    public function insertWithReturningPks(string $table, array|QueryInterface $columns, array &$params = []): string
    {
        $insertSql = $this->insert($table, $columns, $params);
        $tableSchema = $this->schema->getTableSchema($table);
        $primaryKeys = $tableSchema?->getPrimaryKey() ?? [];

        if (empty($primaryKeys)) {
            return $insertSql;
        }

        $primaryKeys = array_map($this->quoter->quoteColumnName(...), $primaryKeys);

        return $insertSql . ' RETURNING ' . implode(', ', $primaryKeys);
    }

    public function resetSequence(string $table, int|string|null $value = null): string
    {
        $tableSchema = $this->schema->getTableSchema($table);

        if ($tableSchema === null) {
            throw new InvalidArgumentException("Table not found: '$table'.");
        }

        $sequence = $tableSchema->getSequenceName();

        if ($sequence === null) {
            throw new InvalidArgumentException("There is not sequence associated with table '$table'.");
        }

        /** @link https://www.postgresql.org/docs/8.1/static/functions-sequence.html */
        $sequence = $this->quoter->quoteTableName($sequence);

        if ($value === null) {
            $table = $this->quoter->quoteTableName($table);
            $key = $tableSchema->getPrimaryKey()[0];
            $key = $this->quoter->quoteColumnName($key);
            $value = "(SELECT COALESCE(MAX($key),0) FROM $table)+1";
        }

        return "SELECT SETVAL('$sequence',$value,false)";
    }

    public function upsert(
        string $table,
        array|QueryInterface $insertColumns,
        array|bool $updateColumns = true,
        array &$params = [],
    ): string {
        $insertSql = $this->insert($table, $insertColumns, $params);

        [$uniqueNames, , $updateNames] = $this->prepareUpsertColumns($table, $insertColumns, $updateColumns);

        if (empty($uniqueNames)) {
            return $insertSql;
        }

        if ($updateColumns === false || $updateNames === []) {
            /** there are no columns to update */
            return "$insertSql ON CONFLICT DO NOTHING";
        }

        if ($updateColumns === true) {
            $updateColumns = [];

            /** @psalm-var string[] $updateNames */
            foreach ($updateNames as $name) {
                $updateColumns[$name] = new Expression(
                    'EXCLUDED.' . $this->quoter->quoteColumnName($name)
                );
            }
        }

        $quotedUniqueNames = array_map($this->quoter->quoteColumnName(...), $uniqueNames);
        $updates = $this->prepareUpdateSets($table, $updateColumns, $params);

        return $insertSql
            . ' ON CONFLICT (' . implode(', ', $quotedUniqueNames) . ')'
            . ' DO UPDATE SET ' . implode(', ', $updates);
    }

    public function upsertWithReturning(
        string $table,
        array|QueryInterface $insertColumns,
        array|bool $updateColumns = true,
        array|null $returnColumns = null,
        array &$params = [],
    ): string {
        $upsertSql = $this->upsert($table, $insertColumns, $updateColumns, $params);

        $returnColumns ??= $this->schema->getTableSchema($table)?->getColumnNames();

        if (empty($returnColumns)) {
            return $upsertSql;
        }

        $returnColumns = array_map($this->quoter->quoteColumnName(...), $returnColumns);

        if (str_ends_with($upsertSql, ' ON CONFLICT DO NOTHING')) {
            $tableName = $this->quoter->quoteTableName($table);
            $dummyColumn = $this->getDummyColumn($table);

            $uniqueNames = $this->prepareUpsertColumns($table, $insertColumns, $updateColumns)[0];
            $quotedUniqueNames = array_map($this->quoter->quoteColumnName(...), $uniqueNames);

            $upsertSql = substr($upsertSql, 0, -10)
                . '(' . implode(', ', $quotedUniqueNames) . ')'
                . " DO UPDATE SET $dummyColumn = $tableName.$dummyColumn";
        }

        return $upsertSql . ' RETURNING ' . implode(', ', $returnColumns);
    }

    private function getDummyColumn(string $table): string
    {
        /** @psalm-suppress PossiblyNullReference */
        $columns = $this->schema->getTableSchema($table)->getColumns();

        foreach ($columns as $column) {
            if ($column->isPrimaryKey() || $column->isUnique()) {
                continue;
            }

            /** @psalm-suppress PossiblyNullArgument */
            return $this->quoter->quoteColumnName($column->getName());
        }

        /** @psalm-suppress PossiblyNullArgument, PossiblyFalseReference */
        return $this->quoter->quoteColumnName(end($columns)->getName());
    }
}
