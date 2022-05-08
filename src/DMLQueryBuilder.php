<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql;

use Generator;
use JsonException;
use PDO;
use Yiisoft\Db\Driver\PDO\PDOValue;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Query\DMLQueryBuilder as AbstractDMLQueryBuilder;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Query\QueryBuilderInterface;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\Schema\QuoterInterface;
use Yiisoft\Db\Schema\Schema;
use Yiisoft\Db\Schema\SchemaInterface;
use Yiisoft\Strings\NumericHelper;

use function implode;
use function is_array;
use function is_string;
use function reset;

final class DMLQueryBuilder extends AbstractDMLQueryBuilder
{
    public function __construct(
        private QueryBuilderInterface $queryBuilder,
        private QuoterInterface $quoter,
        private SchemaInterface $schema
    ) {
        parent::__construct($queryBuilder, $quoter, $schema);
    }

    public function insertEx(string $table, QueryInterface|array $columns, array &$params = []): string
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

    /**
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     *
     * @psalm-suppress MixedArrayOffset
     */
    public function batchInsert(string $table, array $columns, iterable|Generator $rows, array &$params = []): string
    {
        if (empty($rows)) {
            return '';
        }

        /**
         * @psalm-var array<array-key, ColumnSchema> $columnSchemas
         */
        $columnSchemas = [];
        $schema = $this->schema;

        if (($tableSchema = $schema->getTableSchema($table)) !== null) {
            $columnSchemas = $tableSchema->getColumns();
        }

        $values = [];

        /**
         * @var array $row
         */
        foreach ($rows as $row) {
            $vs = [];
            /**
             *  @var int $i
             *  @var mixed $value
             */
            foreach ($row as $i => $value) {
                if (isset($columns[$i], $columnSchemas[$columns[$i]])) {
                    /**
                     * @var bool|ExpressionInterface|float|int|string|null $value
                     */
                    $value = $columnSchemas[$columns[$i]]->dbTypecast($value);
                }

                if (is_string($value)) {
                    /** @var mixed */
                    $value = $this->quoter->quoteValue($value);
                } elseif (is_float($value)) {
                    /** ensure type cast always has . as decimal separator in all locales */
                    $value = NumericHelper::normalize((string) $value);
                } elseif ($value === true) {
                    $value = 'TRUE';
                } elseif ($value === false) {
                    $value = 'FALSE';
                } elseif ($value === null) {
                    $value = 'NULL';
                } elseif ($value instanceof ExpressionInterface) {
                    $value = $this->queryBuilder->buildExpression($value, $params);
                }

                /** @var bool|ExpressionInterface|float|int|string|null $value */
                $vs[] = $value;
            }
            $values[] = '(' . implode(', ', $vs) . ')';
        }

        if (empty($values)) {
            return '';
        }

        /** @var string name */
        foreach ($columns as $i => $name) {
            $columns[$i] = $this->quoter->quoteColumnName($name);
        }

        /**
         * @psalm-var string[] $columns
         * @psalm-var string[] $values
         */
        return 'INSERT INTO '
            . $this->quoter->quoteTableName($table)
            . ' (' . implode(', ', $columns) . ') VALUES ' . implode(', ', $values);
    }

    /**
     * Creates an INSERT SQL statement.
     *
     * For example,
     *
     * ```php
     * $sql = $queryBuilder->insert('user', [
     *     'name' => 'Sam',
     *     'age' => 30,
     * ], $params);
     * ```
     *
     * The method will properly escape the table and column names.
     *
     * @param string $table the table that new rows will be inserted into.
     * @param array|QueryInterface $columns the column data (name => value) to be inserted into the table or instance of
     * {@see Query} to perform INSERT INTO ... SELECT SQL statement. Passing of {@see Query}.
     * @param array $params the binding parameters that will be generated by this method. They should be bound to the
     * DB command later.
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     *
     * @return string the INSERT SQL
     *
     * @psalm-suppress UndefinedInterfaceMethod
     * @psalm-suppress MixedArgument
     */
    public function insert(string $table, QueryInterface|array $columns, array &$params = []): string
    {
        return parent::insert($table, $this->normalizeTableRowData($table, $columns), $params);
    }

    /**
     * {@see upsert()} implementation for PostgresSQL 9.5 or higher.
     *
     * @param string $table
     * @param array|QueryInterface $insertColumns
     * @param array|bool|QueryInterface $updateColumns
     * @param array $params
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|JsonException|NotSupportedException
     *
     * @return string
     */
    public function newUpsert(
        string $table,
        QueryInterface|array $insertColumns,
        bool|array|QueryInterface $updateColumns,
        array &$params = []
    ): string {
        $insertSql = $this->insert($table, $insertColumns, $params);

        /** @var array $uniqueNames */
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

            /** @var string $name */
            foreach ($updateNames as $name) {
                $updateColumns[$name] = new Expression(
                    'EXCLUDED.' . $this->quoter->quoteColumnName($name)
                );
            }
        }

        /**
         * @var array $updateColumns
         *
         * @psalm-var string[] $uniqueNames
         * @psalm-var string[] $updates
         */
        [$updates, $params] = $this->prepareUpdateSets($table, $updateColumns, $params);

        return $insertSql
            . ' ON CONFLICT (' . implode(', ', $uniqueNames) . ') DO UPDATE SET ' . implode(', ', $updates);
    }

    /**
     * Creates a SQL statement for resetting the sequence value of a table's primary key.
     *
     * The sequence will be reset such that the primary key of the next new row inserted will have the specified value
     * or 1.
     *
     * @param string $tableName the name of the table whose primary key sequence will be reset.
     * @param mixed $value the value for the primary key of the next new row inserted. If this is not set, the next new
     * row's primary key will have a value 1.
     *
     * @throws Exception|InvalidArgumentException if the table does not exist or there is no sequence
     * associated with the table.
     *
     * @return string the SQL statement for resetting sequence.
     */
    public function resetSequence(string $tableName, $value = null): string
    {
        $table = $this->schema->getTableSchema($tableName);

        if ($table !== null && ($sequence = $table->getSequenceName()) !== null) {
            /**
             * {@see http://www.postgresql.org/docs/8.1/static/functions-sequence.html}
             */
            $sequence = $this->quoter->quoteTableName($sequence);
            $tableName = $this->quoter->quoteTableName($tableName);

            if ($value === null) {
                $pk = $table->getPrimaryKey();
                $key = $this->quoter->quoteColumnName(reset($pk));
                $value = "(SELECT COALESCE(MAX($key),0) FROM $tableName)+1";
            } else {
                $value = (int) $value;
            }

            return "SELECT SETVAL('$sequence',$value,false)";
        }

        if ($table === null) {
            throw new InvalidArgumentException("Table not found: $tableName");
        }

        throw new InvalidArgumentException("There is not sequence associated with table '$tableName'.");
    }

    public function truncateTable(string $table): string
    {
        return 'TRUNCATE TABLE ' . $this->quoter->quoteTableName($table) . ' RESTART IDENTITY';
    }

    /**
     * Creates an UPDATE SQL statement.
     *
     * For example,
     *
     * ```php
     * $params = [];
     * $sql = $queryBuilder->update('user', ['status' => 1], 'age > 30', $params);
     * ```
     *
     * The method will properly escape the table and column names.
     *
     * @param string $table the table to be updated.
     * @param array $columns the column data (name => value) to be updated.
     * @param array|string $condition the condition that will be put in the WHERE part. Please refer to
     * {@see Query::where()} on how to specify condition.
     * @param array $params the binding parameters that will be modified by this method so that they can be bound to the
     * DB command later.
     *
     * @return string the UPDATE SQL.
     *
     * @psalm-suppress UndefinedInterfaceMethod
     */
    public function update(string $table, array $columns, array|string $condition, array &$params = []): string
    {
        $normalizeTableRowData = $this->normalizeTableRowData($table, $columns);

        return parent::update(
            $table,
            is_array($normalizeTableRowData) ? $normalizeTableRowData : [],
            $condition,
            $params,
        );
    }

    /**
     * Creates an SQL statement to insert rows into a database table if they do not already exist (matching unique
     * constraints), or update them if they do.
     *
     * For example,
     *
     * ```php
     * $sql = $queryBuilder->upsert('pages', [
     *     'name' => 'Front page',
     *     'url' => 'http://example.com/', // url is unique
     *     'visits' => 0,
     * ], [
     *     'visits' => new \Yiisoft\Db\Expression('visits + 1'),
     * ], $params);
     * ```
     *
     * The method will properly escape the table and column names.
     *
     * @param string $table the table that new rows will be inserted into/updated in.
     * @param array|QueryInterface $insertColumns the column data (name => value) to be inserted into the table or
     * instance of {@see Query} to perform `INSERT INTO ... SELECT` SQL statement.
     * @param array|bool|QueryInterface $updateColumns the column data (name => value) to be updated if they already
     * exist.
     * If `true` is passed, the column data will be updated to match the insert column data.
     * If `false` is passed, no update will be performed if the column data already exists.
     * @param array $params the binding parameters that will be generated by this method.
     * They should be bound to the DB command later.
     *
     * @throws Exception|InvalidConfigException|JsonException|NotSupportedException if this is not supported by the
     * underlying DBMS.
     *
     * @return string the resulting SQL.
     *
     * @link https://www.postgresql.org/docs/9.5/static/sql-insert.html#SQL-ON-CONFLICT
     * @link https://stackoverflow.com/questions/1109061/insert-on-duplicate-update-in-postgresql/8702291#8702291
     *
     * @psalm-suppress UndefinedInterfaceMethod
     */
    public function upsert(
        string $table,
        QueryInterface|array $insertColumns,
        $updateColumns,
        array &$params = []
    ): string {
        $insertColumns = $this->normalizeTableRowData($table, $insertColumns);

        if (!is_bool($updateColumns)) {
            $updateColumns = $this->normalizeTableRowData($table, $updateColumns);
        }

        return $this->newUpsert($table, $insertColumns, $updateColumns, $params);
    }

    /**
     * Normalizes data to be saved into the table, performing extra preparations and type converting, if necessary.
     *
     * @param string $table the table that data will be saved into.
     * @param array|QueryInterface $columns the column data (name => value) to be saved into the table or instance of
     * {@see QueryInterface} to perform INSERT INTO ... SELECT SQL statement. Passing of
     * {@see QueryInterface}.
     *
     * @return array|QueryInterface normalized columns.
     */
    private function normalizeTableRowData(string $table, QueryInterface|array $columns): QueryInterface|array
    {
        if ($columns instanceof QueryInterface) {
            return $columns;
        }

        if (($tableSchema = $this->schema->getTableSchema($table)) !== null) {
            $columnSchemas = $tableSchema->getColumns();
            /** @var mixed $value */
            foreach ($columns as $name => $value) {
                if (
                    isset($columnSchemas[$name]) &&
                    $columnSchemas[$name]->getType() === Schema::TYPE_BINARY &&
                    is_string($value)
                ) {
                    /** explicitly setup PDO param type for binary column */
                    $columns[$name] = new PDOValue($value, PDO::PARAM_LOB);
                }
            }
        }

        return $columns;
    }
}
