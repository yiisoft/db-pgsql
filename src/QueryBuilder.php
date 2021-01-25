<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql;

use Generator;
use JsonException;
use PDO;
use Yiisoft\Db\Constraint\Constraint;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ArrayExpression;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Expression\JsonExpression;
use Yiisoft\Db\Pdo\PdoValue;
use Yiisoft\Db\Query\Conditions\LikeCondition;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Query\QueryBuilder as AbstractQueryBuilder;
use Yiisoft\Db\Schema\ColumnSchemaBuilder;
use Yiisoft\Strings\NumericHelper;

use function array_diff;
use function array_merge;
use function array_unshift;
use function explode;
use function implode;
use function is_bool;
use function is_float;
use function is_string;
use function preg_match;
use function preg_replace;
use function reset;
use function strpos;

/**
 * The class QueryBuilder is the query builder for PostgreSQL databases.
 */
final class QueryBuilder extends AbstractQueryBuilder
{
    /**
     * Defines a UNIQUE index for {@see createIndex()}.
     */
    public const INDEX_UNIQUE = 'unique';

    /**
     * Defines a B-tree index for {@see createIndex()}.
     */
    public const INDEX_B_TREE = 'btree';

    /**
     * Defines a hash index for {@see createIndex()}.
     */
    public const INDEX_HASH = 'hash';

    /**
     * Defines a GiST index for {@see createIndex()}.
     */
    public const INDEX_GIST = 'gist';

    /**
     * Defines a GIN index for {@see createIndex()}.
     */
    public const INDEX_GIN = 'gin';

    /**
     * @var array mapping from abstract column types (keys) to physical column types (values).
     */
    protected array $typeMap = [
        Schema::TYPE_PK => 'serial NOT NULL PRIMARY KEY',
        Schema::TYPE_UPK => 'serial NOT NULL PRIMARY KEY',
        Schema::TYPE_BIGPK => 'bigserial NOT NULL PRIMARY KEY',
        Schema::TYPE_UBIGPK => 'bigserial NOT NULL PRIMARY KEY',
        Schema::TYPE_CHAR => 'char(1)',
        Schema::TYPE_STRING => 'varchar(255)',
        Schema::TYPE_TEXT => 'text',
        Schema::TYPE_TINYINT => 'smallint',
        Schema::TYPE_SMALLINT => 'smallint',
        Schema::TYPE_INTEGER => 'integer',
        Schema::TYPE_BIGINT => 'bigint',
        Schema::TYPE_FLOAT => 'double precision',
        Schema::TYPE_DOUBLE => 'double precision',
        Schema::TYPE_DECIMAL => 'numeric(10,0)',
        Schema::TYPE_DATETIME => 'timestamp(0)',
        Schema::TYPE_TIMESTAMP => 'timestamp(0)',
        Schema::TYPE_TIME => 'time(0)',
        Schema::TYPE_DATE => 'date',
        Schema::TYPE_BINARY => 'bytea',
        Schema::TYPE_BOOLEAN => 'boolean',
        Schema::TYPE_MONEY => 'numeric(19,4)',
        Schema::TYPE_JSON => 'jsonb',
    ];

    /**
     * Contains array of default condition classes. Extend this method, if you want to change default condition classes
     * for the query builder.
     *
     * @return array
     *
     * See {@see conditionClasses} docs for details.
     */
    protected function defaultConditionClasses(): array
    {
        return array_merge(parent::defaultConditionClasses(), [
            'ILIKE' => LikeCondition::class,
            'NOT ILIKE' => LikeCondition::class,
            'OR ILIKE' => LikeCondition::class,
            'OR NOT ILIKE' => LikeCondition::class,
        ]);
    }

    /**
     * Contains array of default expression builders. Extend this method and override it, if you want to change default
     * expression builders for this query builder.
     *
     * @return array
     *
     * See {@see ExpressionBuilder} docs for details.
     */
    protected function defaultExpressionBuilders(): array
    {
        return array_merge(parent::defaultExpressionBuilders(), [
            ArrayExpression::class => ArrayExpressionBuilder::class,
            JsonExpression::class => JsonExpressionBuilder::class,
        ]);
    }

    /**
     * Builds a SQL statement for creating a new index.
     *
     * @param string $name the name of the index. The name will be properly quoted by the method.
     * @param string $table the table that the new index will be created for. The table name will be properly quoted by
     * the method.
     * @param array|string $columns the column(s) that should be included in the index. If there are multiple columns,
     * separate them with commas or use an array to represent them. Each column name will be properly quoted by the
     * method, unless a parenthesis is found in the name.
     * @param bool|string $unique whether to make this a UNIQUE index constraint. You can pass `true` or
     * {@see INDEX_UNIQUE} to create a unique index, `false` to make a non-unique index using the default index type, or
     * one of the following constants to specify the index method to use: {@see INDEX_B_TREE}, {@see INDEX_HASH},
     * {@see INDEX_GIST}, {@see INDEX_GIN}.
     *
     * @throws Exception|InvalidArgumentException
     *
     * @return string the SQL statement for creating a new index.
     *
     * {@see http://www.postgresql.org/docs/8.2/static/sql-createindex.html}
     */
    public function createIndex(string $name, string $table, $columns, $unique = false): string
    {
        if ($unique === self::INDEX_UNIQUE || $unique === true) {
            $index = false;
            $unique = true;
        } else {
            $index = $unique;
            $unique = false;
        }

        return ($unique ? 'CREATE UNIQUE INDEX ' : 'CREATE INDEX ')
            . $this->getDb()->quoteTableName($name) . ' ON '
            . $this->getDb()->quoteTableName($table)
            . ($index !== false ? " USING $index" : '')
            . ' (' . $this->buildColumns($columns) . ')';
    }

    /**
     * Builds a SQL statement for dropping an index.
     *
     * @param string $name the name of the index to be dropped. The name will be properly quoted by the method.
     * @param string $table the table whose index is to be dropped. The name will be properly quoted by the method.
     *
     * @return string the SQL statement for dropping an index.
     */
    public function dropIndex(string $name, string $table): string
    {
        if (strpos($table, '.') !== false && strpos($name, '.') === false) {
            if (strpos($table, '{{') !== false) {
                $table = preg_replace('/{{(.*?)}}/', '\1', $table);
                [$schema, $table] = explode('.', $table);
                if (strpos($schema, '%') === false) {
                    $name = $schema . '.' . $name;
                } else {
                    $name = '{{' . $schema . '.' . $name . '}}';
                }
            } else {
                [$schema] = explode('.', $table);
                $name = $schema . '.' . $name;
            }
        }

        return 'DROP INDEX ' . $this->getDb()->quoteTableName($name);
    }

    /**
     * Builds a SQL statement for renaming a DB table.
     *
     * @param string $oldName the table to be renamed. The name will be properly quoted by the method.
     * @param string $newName the new table name. The name will be properly quoted by the method.
     *
     * @return string the SQL statement for renaming a DB table.
     */
    public function renameTable(string $oldName, string $newName): string
    {
        return 'ALTER TABLE ' . $this->getDb()->quoteTableName($oldName) . ' RENAME TO '
            . $this->getDb()->quoteTableName($newName);
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
     * @throws Exception|InvalidArgumentException|JsonException if the table does not exist or there is no sequence
     * associated with the table.
     *
     * @return string the SQL statement for resetting sequence.
     */
    public function resetSequence(string $tableName, $value = null): string
    {
        $table = $this->getDb()->getTableSchema($tableName);

        if ($table !== null && ($sequence = $table->getSequenceName()) !== null) {
            /**
             * {@see http://www.postgresql.org/docs/8.1/static/functions-sequence.html}
             */
            $sequence = $this->getDb()->quoteTableName($sequence);
            $tableName = $this->getDb()->quoteTableName($tableName);

            if ($value === null) {
                $pk = $table->getPrimaryKey();
                $key = $this->getDb()->quoteColumnName(reset($pk));
                $value = "(SELECT COALESCE(MAX({$key}),0) FROM {$tableName})+1";
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

    /**
     * Builds a SQL statement for enabling or disabling integrity check.
     *
     * @param string $schema the schema of the tables.
     * @param string $table the table name.
     * @param bool $check whether to turn on or off the integrity check.
     *
     * @throws Exception|NotSupportedException
     *
     * @return string the SQL statement for checking integrity.
     */
    public function checkIntegrity(string $schema = '', string $table = '', bool $check = true): string
    {
        /** @psalm-var Connection $db */
        $db = $this->getDb();

        $enable = $check ? 'ENABLE' : 'DISABLE';
        $schema = $schema ?: $db->getSchema()->getDefaultSchema();
        $tableNames = [];
        $viewNames = [];

        if ($schema !== null) {
            $tableNames = $table ? [$table] : $db->getSchema()->getTableNames($schema);
            $viewNames = $db->getSchema()->getViewNames($schema);
        }

        $tableNames = array_diff($tableNames, $viewNames);
        $command = '';

        foreach ($tableNames as $tableName) {
            $tableName = $db->quoteTableName("{$schema}.{$tableName}");
            $command .= "ALTER TABLE $tableName $enable TRIGGER ALL; ";
        }

        /** enable to have ability to alter several tables */
        $db->getMasterPdo()->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

        return $command;
    }

    /**
     * Builds a SQL statement for truncating a DB table.
     *
     * Explicitly restarts identity for PGSQL to be consistent with other databases which all do this by default.
     *
     * @param string $table the table to be truncated. The name will be properly quoted by the method.
     *
     * @return string the SQL statement for truncating a DB table.
     */
    public function truncateTable(string $table): string
    {
        return 'TRUNCATE TABLE ' . $this->getDb()->quoteTableName($table) . ' RESTART IDENTITY';
    }

    /**
     * Builds a SQL statement for changing the definition of a column.
     *
     * @param string $table the table whose column is to be changed. The table name will be properly quoted by the
     * method.
     * @param string $column the name of the column to be changed. The name will be properly quoted by the method.
     * @param ColumnSchemaBuilder|string $type the new column type. The {@see getColumnType()} method will be invoked to
     * convert abstract column type (if any) into the physical one. Anything that is not recognized as abstract type
     * will be kept in the generated SQL. For example, 'string' will be turned into 'varchar(255)', while
     * 'string not null' will become 'varchar(255) not null'. You can also use PostgreSQL-specific syntax such as
     * `SET NOT NULL`.
     *
     * @return string the SQL statement for changing the definition of a column.
     */
    public function alterColumn(string $table, string $column, $type): string
    {
        $columnName = $this->getDb()->quoteColumnName($column);
        $tableName = $this->getDb()->quoteTableName($table);

        /**
         * {@see https://github.com/yiisoft/yii2/issues/4492}
         * {@see http://www.postgresql.org/docs/9.1/static/sql-altertable.html}
         */
        if (preg_match('/^(DROP|SET|RESET|USING)\s+/i', (string) $type)) {
            return "ALTER TABLE {$tableName} ALTER COLUMN {$columnName} {$type}";
        }

        $type = 'TYPE ' . $this->getColumnType($type);
        $multiAlterStatement = [];
        $constraintPrefix = preg_replace('/[^a-z0-9_]/i', '', $table . '_' . $column);

        if (preg_match('/\s+DEFAULT\s+(["\']?\w*["\']?)/i', $type, $matches)) {
            $type = preg_replace('/\s+DEFAULT\s+(["\']?\w*["\']?)/i', '', $type);
            $multiAlterStatement[] = "ALTER COLUMN {$columnName} SET DEFAULT {$matches[1]}";
        }

        $type = preg_replace('/\s+NOT\s+NULL/i', '', $type, -1, $count);

        if ($count) {
            $multiAlterStatement[] = "ALTER COLUMN {$columnName} SET NOT NULL";
        } else {
            /** remove additional null if any */
            $type = preg_replace('/\s+NULL/i', '', $type, -1, $count);
            if ($count) {
                $multiAlterStatement[] = "ALTER COLUMN {$columnName} DROP NOT NULL";
            }
        }

        if (preg_match('/\s+CHECK\s+\((.+)\)/i', $type, $matches)) {
            $type = preg_replace('/\s+CHECK\s+\((.+)\)/i', '', $type);
            $multiAlterStatement[] = "ADD CONSTRAINT {$constraintPrefix}_check CHECK ({$matches[1]})";
        }

        $type = preg_replace('/\s+UNIQUE/i', '', $type, -1, $count);

        if ($count) {
            $multiAlterStatement[] = "ADD UNIQUE ({$columnName})";
        }

        /** add what's left at the beginning */
        array_unshift($multiAlterStatement, "ALTER COLUMN {$columnName} {$type}");

        return 'ALTER TABLE ' . $tableName . ' ' . implode(', ', $multiAlterStatement);
    }

    /**
     * Creates an INSERT SQL statement.
     *
     * For example,.
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
     * @param array|Query $columns the column data (name => value) to be inserted into the table or instance of
     * {@see Query|Query} to perform INSERT INTO ... SELECT SQL statement. Passing of
     * {@see Query|Query}.
     * @param array $params the binding parameters that will be generated by this method. They should be bound to the
     * DB command later.
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|JsonException|NotSupportedException
     *
     * @return string the INSERT SQL
     */
    public function insert(string $table, $columns, array &$params = []): string
    {
        return parent::insert($table, $this->normalizeTableRowData($table, $columns), $params);
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
     * @param array|Query $insertColumns the column data (name => value) to be inserted into the table or instance of
     * {@see Query} to perform `INSERT INTO ... SELECT` SQL statement.
     * @param array|bool $updateColumns the column data (name => value) to be updated if they already exist.
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
     * {@see https://www.postgresql.org/docs/9.5/static/sql-insert.html#SQL-ON-CONFLICT}
     * {@see https://stackoverflow.com/questions/1109061/insert-on-duplicate-update-in-postgresql/8702291#8702291}
     */
    public function upsert(string $table, $insertColumns, $updateColumns, array &$params = []): string
    {
        $insertColumns = $this->normalizeTableRowData($table, $insertColumns);

        if (!is_bool($updateColumns)) {
            $updateColumns = $this->normalizeTableRowData($table, $updateColumns);
        }

        return $this->newUpsert($table, $insertColumns, $updateColumns, $params);
    }

    /**
     * {@see upsert()} implementation for PostgreSQL 9.5 or higher.
     *
     * @param string $table
     * @param array|Query $insertColumns
     * @param array|bool $updateColumns
     * @param array $params
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|JsonException|NotSupportedException
     *
     * @return string
     */
    private function newUpsert(string $table, $insertColumns, $updateColumns, array &$params = []): string
    {
        $insertSql = $this->insert($table, $insertColumns, $params);

        /** @var array<array-key, mixed> $uniqueNames */
        [$uniqueNames, , $updateNames] = $this->prepareUpsertColumns($table, $insertColumns, $updateColumns);

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
                $updateColumns[$name] = new Expression('EXCLUDED.' . $this->getDb()->quoteColumnName($name));
            }
        }

        /** @var array<array-key, mixed> $updates */
        [$updates, $params] = $this->prepareUpdateSets($table, $updateColumns, $params);

        return $insertSql . ' ON CONFLICT (' . implode(', ', $uniqueNames) . ') DO UPDATE SET '
            . implode(', ', $updates);
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
     * @throws Exception|InvalidArgumentException|JsonException
     *
     * @return string the UPDATE SQL.
     */
    public function update(string $table, array $columns, $condition, array &$params = []): string
    {
        return parent::update($table, $this->normalizeTableRowData($table, $columns), $condition, $params);
    }

    /**
     * Normalizes data to be saved into the table, performing extra preparations and type converting, if necessary.
     *
     * @param string $table the table that data will be saved into.
     * @param array|Query $columns the column data (name => value) to be saved into the table or instance of
     * {@see Query} to perform INSERT INTO ... SELECT SQL statement. Passing of
     * {@see Query}.
     *
     * @return mixed normalized columns.
     */
    private function normalizeTableRowData(string $table, $columns)
    {
        if ($columns instanceof Query) {
            return $columns;
        }

        if (($tableSchema = $this->getDb()->getSchema()->getTableSchema($table)) !== null) {
            $columnSchemas = $tableSchema->getColumns();
            /** @var mixed $value */
            foreach ($columns as $name => $value) {
                if (
                    isset($columnSchemas[$name]) &&
                    $columnSchemas[$name]->getType() === Schema::TYPE_BINARY &&
                    is_string($value)
                ) {
                    /** explicitly setup PDO param type for binary column */
                    $columns[$name] = new PdoValue($value, PDO::PARAM_LOB);
                }
            }
        }

        return $columns;
    }

    /**
     * Generates a batch INSERT SQL statement.
     *
     * For example,
     *
     * ```php
     * $sql = $queryBuilder->batchInsert('user', ['name', 'age'], [
     *     ['Tom', 30],
     *     ['Jane', 20],
     *     ['Linda', 25],
     * ]);
     * ```
     *
     * Note that the values in each row must match the corresponding column names.
     *
     * The method will properly escape the column names, and quote the values to be inserted.
     *
     * @param string $table the table that new rows will be inserted into.
     * @param array<array-key, mixed> $columns the column names.
     * @param array|Generator $rows the rows to be batch inserted into the table.
     * @param array $params the binding parameters. This parameter exists.
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     *
     * @return string the batch INSERT SQL statement.
     */
    public function batchInsert(string $table, array $columns, $rows, array &$params = []): string
    {
        if (empty($rows)) {
            return '';
        }

        /**
         * @var array<array-key, object> $columnSchemas
         */
        $columnSchemas = [];
        $schema = $this->getDb()->getSchema();

        if (($tableSchema = $schema->getTableSchema($table)) !== null) {
            $columnSchemas = $tableSchema->getColumns();
        }

        $values = [];

        /**
         * @var array<array-key, mixed> $row
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
                    $value = $schema->quoteValue($value);
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
                    $value = $this->buildExpression($value, $params);
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
            $columns[$i] = $schema->quoteColumnName($name);
        }

        return 'INSERT INTO ' . $schema->quoteTableName($table)
            . ' (' . implode(', ', $columns) . ') VALUES ' . implode(', ', $values);
    }
}
