<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql;

use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\QueryBuilder\AbstractDMLQueryBuilder;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;
use Yiisoft\Db\Schema\QuoterInterface;
use Yiisoft\Db\Schema\SchemaInterface;

use function implode;
use function reset;

/**
 * Implements a DML (Data Manipulation Language) SQL statements for PostgreSQL Server.
 */
final class DMLQueryBuilder extends AbstractDMLQueryBuilder
{
    public function __construct(
        private QueryBuilderInterface $queryBuilder,
        private QuoterInterface $quoter,
        private SchemaInterface $schema
    ) {
        parent::__construct($queryBuilder, $quoter, $schema);
    }

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

    public function resetSequence(string $tableName, int|string $value = null): string
    {
        $table = $this->schema->getTableSchema($tableName);

        if ($table !== null && ($sequence = $table->getSequenceName()) !== null) {
            /**
             * @link https://www.postgresql.org/docs/8.1/static/functions-sequence.html
             */
            $sequence = $this->quoter->quoteTableName($sequence);
            $tableName = $this->quoter->quoteTableName($tableName);

            if ($value === null) {
                $pk = $table->getPrimaryKey();
                $key = $this->quoter->quoteColumnName(reset($pk));
                $value = "(SELECT COALESCE(MAX($key),0) FROM $tableName)+1";
            }

            return "SELECT SETVAL('$sequence',$value,false)";
        }

        if ($table === null) {
            throw new InvalidArgumentException("Table not found: '$tableName'.");
        }

        throw new InvalidArgumentException("There is not sequence associated with table '$tableName'.");
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
