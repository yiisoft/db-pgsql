<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Query;

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
use Yiisoft\Db\Pgsql\Expression\ArrayExpressionBuilder;
use Yiisoft\Db\Pgsql\Expression\JsonExpressionBuilder;
use Yiisoft\Db\Pgsql\Schema\PgsqlSchema;
use Yiisoft\Db\Query\Conditions\LikeCondition;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Query\QueryBuilder;
use Yiisoft\Strings\StringHelper;

use function array_diff;
use function array_merge;
use function array_unshift;
use function count;
use function explode;
use function implode;
use function is_bool;
use function is_float;
use function is_string;
use function preg_match;
use function preg_replace;
use function reset;
use function strpos;
use function strrpos;
use function version_compare;

final class PgsqlQueryBuilder extends QueryBuilder
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
        PgsqlSchema::TYPE_PK => 'serial NOT NULL PRIMARY KEY',
        PgsqlSchema::TYPE_UPK => 'serial NOT NULL PRIMARY KEY',
        PgsqlSchema::TYPE_BIGPK => 'bigserial NOT NULL PRIMARY KEY',
        PgsqlSchema::TYPE_UBIGPK => 'bigserial NOT NULL PRIMARY KEY',
        PgsqlSchema::TYPE_CHAR => 'char(1)',
        PgsqlSchema::TYPE_STRING => 'varchar(255)',
        PgsqlSchema::TYPE_TEXT => 'text',
        PgsqlSchema::TYPE_TINYINT => 'smallint',
        PgsqlSchema::TYPE_SMALLINT => 'smallint',
        PgsqlSchema::TYPE_INTEGER => 'integer',
        PgsqlSchema::TYPE_BIGINT => 'bigint',
        PgsqlSchema::TYPE_FLOAT => 'double precision',
        PgsqlSchema::TYPE_DOUBLE => 'double precision',
        PgsqlSchema::TYPE_DECIMAL => 'numeric(10,0)',
        PgsqlSchema::TYPE_DATETIME => 'timestamp(0)',
        PgsqlSchema::TYPE_TIMESTAMP => 'timestamp(0)',
        PgsqlSchema::TYPE_TIME => 'time(0)',
        PgsqlSchema::TYPE_DATE => 'date',
        PgsqlSchema::TYPE_BINARY => 'bytea',
        PgsqlSchema::TYPE_BOOLEAN => 'boolean',
        PgsqlSchema::TYPE_MONEY => 'numeric(19,4)',
        PgsqlSchema::TYPE_JSON => 'jsonb',
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
     * @param string|array $columns the column(s) that should be included in the index. If there are multiple columns,
     * separate them with commas or use an array to represent them. Each column name will be properly quoted by the
     * method, unless a parenthesis is found in the name.
     * @param bool|string $unique whether to make this a UNIQUE index constraint. You can pass `true` or
     * {@see INDEX_UNIQUE} to create a unique index, `false` to make a non-unique index using the default index type, or
     * one of the following constants to specify the index method to use: {@see INDEX_B_TREE}, {@see INDEX_HASH},
     * {@see INDEX_GIST}, {@see INDEX_GIN}.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * @return string the SQL statement for creating a new index.
     *
     * {@see http://www.postgresql.org/docs/8.2/static/sql-createindex.html}
     */
    public function createIndex(string $name, string $table, $columns, bool $unique = false): string
    {
        if ($unique === self::INDEX_UNIQUE || $unique === true) {
            $index = false;
            $unique = true;
        } else {
            $index = $unique;
            $unique = false;
        }

        return ($unique ? 'CREATE UNIQUE INDEX ' : 'CREATE INDEX ')
            . $this->db->quoteTableName($name) . ' ON '
            . $this->db->quoteTableName($table)
            . ($index !== false ? " USING $index" : '')
            . ' (' . $this->buildColumns($columns) . ')';
    }

    /**
     * Builds a SQL statement for dropping an index.
     *
     * @param string $name the name of the index to be dropped. The name will be properly quoted by the method.
     * @param string $table the table whose index is to be dropped. The name will be properly quoted by the method.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
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

        return 'DROP INDEX ' . $this->db->quoteTableName($name);
    }

    /**
     * Builds a SQL statement for renaming a DB table.
     *
     * @param string $oldName the table to be renamed. The name will be properly quoted by the method.
     * @param string $newName the new table name. The name will be properly quoted by the method.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * @return string the SQL statement for renaming a DB table.
     */
    public function renameTable(string $oldName, string $newName): string
    {
        return 'ALTER TABLE ' . $this->db->quoteTableName($oldName) . ' RENAME TO '
            . $this->db->quoteTableName($newName);
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
     * @throws Exception
     * @throws InvalidArgumentException if the table does not exist or there is no sequence associated with the table.
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * @return string the SQL statement for resetting sequence.
     */
    public function resetSequence(string $tableName, $value = null): string
    {
        $table = $this->db->getTableSchema($tableName);
        if ($table !== null && $table->getSequenceName() !== null) {
            /**
             * {@see http://www.postgresql.org/docs/8.1/static/functions-sequence.html}
             */
            $sequence = $this->db->quoteTableName($table->getSequenceName());
            $tableName = $this->db->quoteTableName($tableName);
            if ($value === null) {
                $pk = $table->getPrimaryKey();
                $key = $this->db->quoteColumnName(reset($pk));
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
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * @return string the SQL statement for checking integrity.
     */
    public function checkIntegrity(string $schema = '', string $table = '', bool $check = true): string
    {
        $enable = $check ? 'ENABLE' : 'DISABLE';
        $schema = $schema ?: $this->db->getSchema()->getDefaultSchema();
        $tableNames = $table ? [$table] : $this->db->getSchema()->getTableNames($schema);
        $viewNames = $this->db->getSchema()->getViewNames($schema);
        $tableNames = array_diff($tableNames, $viewNames);
        $command = '';

        foreach ($tableNames as $tableName) {
            $tableName = $this->db->quoteTableName("{$schema}.{$tableName}");
            $command .= "ALTER TABLE $tableName $enable TRIGGER ALL; ";
        }

        /** enable to have ability to alter several tables */
        $this->db->getMasterPdo()->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

        return $command;
    }

    /**
     * Builds a SQL statement for truncating a DB table.
     *
     * Explicitly restarts identity for PGSQL to be consistent with other databases which all do this by default.
     *
     * @param string $table the table to be truncated. The name will be properly quoted by the method.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * @return string the SQL statement for truncating a DB table.
     */
    public function truncateTable(string $table): string
    {
        return 'TRUNCATE TABLE ' . $this->db->quoteTableName($table) . ' RESTART IDENTITY';
    }

    /**
     * Builds a SQL statement for changing the definition of a column.
     *
     * @param string $table the table whose column is to be changed. The table name will be properly quoted by the
     * method.
     * @param string $column the name of the column to be changed. The name will be properly quoted by the method.
     * @param string $type the new column type. The {@see getColumnType()} method will be invoked to convert abstract
     * column type (if any) into the physical one. Anything that is not recognized as abstract type will be kept in the
     * generated SQL. For example, 'string' will be turned into 'varchar(255)', while 'string not null' will become
     * 'varchar(255) not null'. You can also use PostgreSQL-specific syntax such as `SET NOT NULL`.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * @return string the SQL statement for changing the definition of a column.
     */
    public function alterColumn(string $table, string $column, $type): string
    {
        $columnName = $this->db->quoteColumnName($column);
        $tableName = $this->db->quoteTableName($table);

        /**
         * {@see https://github.com/yiisoft/yii2/issues/4492}
         * {@see http://www.postgresql.org/docs/9.1/static/sql-altertable.html}
         */
        if (preg_match('/^(DROP|SET|RESET)\s+/i', (string) $type)) {
            return "ALTER TABLE {$tableName} ALTER COLUMN {$columnName} {$type}";
        }

        $type = 'TYPE ' . $this->getColumnType($type);
        $multiAlterStatement = [];
        $constraintPrefix = preg_replace('/[^a-z0-9_]/i', '', $table . '_' . $column);

        if (preg_match('/\s+DEFAULT\s+(["\']?\w*["\']?)/i', $type, $matches)) {
            $type = preg_replace('/\s+DEFAULT\s+(["\']?\w*["\']?)/i', '', $type);
            $multiAlterStatement[] = "ALTER COLUMN {$columnName} SET DEFAULT {$matches[1]}";
        } else {
            /* safe to drop default even if there was none in the first place */
            $multiAlterStatement[] = "ALTER COLUMN {$columnName} DROP DEFAULT";
        }

        $type = preg_replace('/\s+NOT\s+NULL/i', '', $type, -1, $count);

        if ($count) {
            $multiAlterStatement[] = "ALTER COLUMN {$columnName} SET NOT NULL";
        } else {
            /* remove additional null if any */
            $type = preg_replace('/\s+NULL/i', '', $type);
            /* safe to drop not null even if there was none in the first place */
            $multiAlterStatement[] = "ALTER COLUMN {$columnName} DROP NOT NULL";
        }

        if (preg_match('/\s+CHECK\s+\((.+)\)/i', $type, $matches)) {
            $type = preg_replace('/\s+CHECK\s+\((.+)\)/i', '', $type);
            $multiAlterStatement[] = "ADD CONSTRAINT {$constraintPrefix}_check CHECK ({$matches[1]})";
        }

        $type = preg_replace('/\s+UNIQUE/i', '', $type, -1, $count);

        if ($count) {
            $multiAlterStatement[] = "ADD UNIQUE ({$columnName})";
        }

        /* add what's left at the beginning */
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
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
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
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException if this is not supported by the underlying DBMS.
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
        if (version_compare($this->db->getServerVersion(), '9.5', '<')) {
            return $this->oldUpsert($table, $insertColumns, $updateColumns, $params);
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
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * @return string
     */
    private function newUpsert(string $table, $insertColumns, $updateColumns, array &$params = []): string
    {
        $insertSql = $this->insert($table, $insertColumns, $params);
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
            foreach ($updateNames as $name) {
                $updateColumns[$name] = new Expression('EXCLUDED.' . $this->db->quoteColumnName($name));
            }
        }

        [$updates, $params] = $this->prepareUpdateSets($table, $updateColumns, $params);

        return $insertSql . ' ON CONFLICT (' . implode(', ', $uniqueNames) . ') DO UPDATE SET '
            . implode(', ', $updates);
    }

    /**
     * {@see upsert()} implementation for PostgreSQL older than 9.5.
     *
     * @param string $table
     * @param array|Query $insertColumns
     * @param array|bool $updateColumns
     * @param array $params
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * @return string
     */
    private function oldUpsert(string $table, $insertColumns, $updateColumns, array &$params = []): string
    {
        /** @var Constraint[] $constraints */
        [$uniqueNames, $insertNames, $updateNames] = $this->prepareUpsertColumns(
            $table,
            $insertColumns,
            $updateColumns,
            $constraints
        );

        if (empty($uniqueNames)) {
            return $this->insert($table, $insertColumns, $params);
        }

        if ($updateNames === []) {
            /** there are no columns to update */
            $updateColumns = false;
        }

        /** @var PgsqlSchema $schema */
        $schema = $this->db->getSchema();

        if (!$insertColumns instanceof Query) {
            $tableSchema = $schema->getTableSchema($table);
            $columnSchemas = $tableSchema !== null ? $tableSchema->getColumns() : [];
            foreach ($insertColumns as $name => $value) {
                /**
                 * NULLs and numeric values must be type hinted in order to be used in SET assigments NVM, let's cast
                 * them all
                 */
                if (isset($columnSchemas[$name])) {
                    $phName = self::PARAM_PREFIX . count($params);
                    $params[$phName] = $value;
                    $insertColumns[$name] = new Expression("CAST($phName AS {$columnSchemas[$name]->getDbType()})");
                }
            }
        }

        [, $placeholders, $values, $params] = $this->prepareInsertValues($table, $insertColumns, $params);
        $updateCondition = ['or'];
        $insertCondition = ['or'];
        $quotedTableName = $schema->quoteTableName($table);

        foreach ($constraints as $constraint) {
            $constraintUpdateCondition = ['and'];
            $constraintInsertCondition = ['and'];
            foreach ($constraint->getColumnNames() as $name) {
                $quotedName = $schema->quoteColumnName($name);
                $constraintUpdateCondition[] = "$quotedTableName.$quotedName=\"EXCLUDED\".$quotedName";
                $constraintInsertCondition[] = "\"upsert\".$quotedName=\"EXCLUDED\".$quotedName";
            }
            $updateCondition[] = $constraintUpdateCondition;
            $insertCondition[] = $constraintInsertCondition;
        }

        $withSql = 'WITH "EXCLUDED" (' . implode(', ', $insertNames) . ') AS ('
            . (!empty($placeholders) ? 'VALUES (' . implode(', ', $placeholders) . ')' : ltrim($values, ' ')) . ')';

        if ($updateColumns === false) {
            $selectSubQuery = (new Query($this->db))
                ->select(new Expression('1'))
                ->from($table)
                ->where($updateCondition);
            $insertSelectSubQuery = (new Query($this->db))
                ->select($insertNames)
                ->from('EXCLUDED')
                ->where(['not exists', $selectSubQuery]);
            $insertSql = $this->insert($table, $insertSelectSubQuery, $params);

            return "$withSql $insertSql";
        }

        if ($updateColumns === true) {
            $updateColumns = [];
            foreach ($updateNames as $name) {
                $quotedName = $this->db->quoteColumnName($name);
                if (strrpos($quotedName, '.') === false) {
                    $quotedName = '"EXCLUDED".' . $quotedName;
                }
                $updateColumns[$name] = new Expression($quotedName);
            }
        }

        [$updates, $params] = $this->prepareUpdateSets($table, $updateColumns, $params);

        $updateSql = 'UPDATE ' . $this->db->quoteTableName($table) . ' SET ' . implode(', ', $updates)
            . ' FROM "EXCLUDED" ' . $this->buildWhere($updateCondition, $params)
            . ' RETURNING ' . $this->db->quoteTableName($table) . '.*';

        $selectUpsertSubQuery = (new Query($this->db))
            ->select(new Expression('1'))
            ->from('upsert')
            ->where($insertCondition);

        $insertSelectSubQuery = (new Query($this->db))
            ->select($insertNames)
            ->from('EXCLUDED')
            ->where(['not exists', $selectUpsertSubQuery]);

        $insertSql = $this->insert($table, $insertSelectSubQuery, $params);

        return "$withSql, \"upsert\" AS ($updateSql) $insertSql";
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
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
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
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * @return array|object normalized columns
     */
    private function normalizeTableRowData(string $table, $columns)
    {
        if ($columns instanceof Query) {
            return $columns;
        }

        if (($tableSchema = $this->db->getSchema()->getTableSchema($table)) !== null) {
            $columnSchemas = $tableSchema->getColumns();
            foreach ($columns as $name => $value) {
                if (
                    isset($columnSchemas[$name]) &&
                    $columnSchemas[$name]->getType() === PgsqlSchema::TYPE_BINARY &&
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
     * @param array $columns the column names.
     * @param array|Generator $rows the rows to be batch inserted into the table.
     * @param array $params the binding parameters. This parameter exists.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * @return string the batch INSERT SQL statement.
     */
    public function batchInsert(string $table, array $columns, $rows, array &$params = []): string
    {
        if (empty($rows)) {
            return '';
        }

        $schema = $this->db->getSchema();

        if (($tableSchema = $schema->getTableSchema($table)) !== null) {
            $columnSchemas = $tableSchema->getColumns();
        } else {
            $columnSchemas = [];
        }

        $values = [];
        foreach ($rows as $row) {
            $vs = [];
            foreach ($row as $i => $value) {
                if (isset($columns[$i], $columnSchemas[$columns[$i]])) {
                    $value = $columnSchemas[$columns[$i]]->dbTypecast($value);
                }

                if (is_string($value)) {
                    $value = $schema->quoteValue($value);
                } elseif (is_float($value)) {
                    /* ensure type cast always has . as decimal separator in all locales */
                    $value = StringHelper::floatToString($value);
                } elseif ($value === true) {
                    $value = 'TRUE';
                } elseif ($value === false) {
                    $value = 'FALSE';
                } elseif ($value === null) {
                    $value = 'NULL';
                } elseif ($value instanceof ExpressionInterface) {
                    $value = $this->buildExpression($value, $params);
                }

                $vs[] = $value;
            }
            $values[] = '(' . implode(', ', $vs) . ')';
        }

        if (empty($values)) {
            return '';
        }

        foreach ($columns as $i => $name) {
            $columns[$i] = $schema->quoteColumnName($name);
        }

        return 'INSERT INTO ' . $schema->quoteTableName($table)
            . ' (' . implode(', ', $columns) . ') VALUES ' . implode(', ', $values);
    }
}
