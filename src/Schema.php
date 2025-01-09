<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql;

use JsonException;
use Throwable;
use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Constraint\CheckConstraint;
use Yiisoft\Db\Constraint\Constraint;
use Yiisoft\Db\Constraint\DefaultValueConstraint;
use Yiisoft\Db\Constraint\ForeignKeyConstraint;
use Yiisoft\Db\Constraint\IndexConstraint;
use Yiisoft\Db\Driver\Pdo\AbstractPdoSchema;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Helper\DbArrayHelper;
use Yiisoft\Db\Pgsql\Column\ColumnFactory;
use Yiisoft\Db\Pgsql\Column\SequenceColumnInterface;
use Yiisoft\Db\Schema\Column\ColumnFactoryInterface;
use Yiisoft\Db\Schema\Column\ColumnInterface;
use Yiisoft\Db\Schema\TableSchemaInterface;

use function array_change_key_case;
use function array_map;
use function array_unique;
use function array_values;
use function explode;
use function in_array;
use function is_string;
use function preg_match;
use function str_replace;
use function str_starts_with;
use function substr;

/**
 * Implements the PostgreSQL Server specific schema, supporting PostgreSQL Server version 9.6 and above.
 *
 * @psalm-type ColumnArray = array{
 *   column_name: string,
 *   data_type: string,
 *   type_type: string|null,
 *   type_scheme: string|null,
 *   character_maximum_length: int|string,
 *   column_comment: string|null,
 *   is_nullable: bool|string,
 *   column_default: string|null,
 *   is_autoinc: bool|string,
 *   sequence_name: string|null,
 *   enum_values: string|null,
 *   size: int|string|null,
 *   scale: int|string|null,
 *   contype: string|null,
 *   dimension: int|string,
 *   schema: string,
 *   table: string
 * }
 * @psalm-type ConstraintArray = array<
 *   array-key,
 *   array {
 *     name: string,
 *     column_name: string,
 *     type: string,
 *     foreign_table_schema: string|null,
 *     foreign_table_name: string|null,
 *     foreign_column_name: string|null,
 *     on_update: string,
 *     on_delete: string,
 *     check_expr: string
 *   }
 * >
 * @psalm-type FindConstraintArray = array{
 *   constraint_name: string,
 *   column_name: string,
 *   foreign_table_name: string,
 *   foreign_table_schema: string,
 *   foreign_column_name: string,
 * }
 * @psalm-type CreateInfo = array{
 *   dimension?: int|string,
 *   columns?: array<string, ColumnInterface>
 * }
 *
 * @psalm-suppress MissingClassConstType
 */
final class Schema extends AbstractPdoSchema
{
    /**
     * @var string|null The default schema used for the current session.
     */
    protected string|null $defaultSchema = 'public';

    public function getColumnFactory(): ColumnFactoryInterface
    {
        return new ColumnFactory();
    }

    /**
     * Resolves the table name and schema name (if any).
     *
     * @param string $name The table name.
     *
     * @return TableSchemaInterface With resolved table, schema, etc. names.
     *
     * @see TableSchemaInterface
     */
    protected function resolveTableName(string $name): TableSchemaInterface
    {
        $resolvedName = new TableSchema();

        $parts = array_reverse($this->db->getQuoter()->getTableNameParts($name));
        $resolvedName->name($parts[0] ?? '');
        $resolvedName->schemaName($parts[1] ?? $this->defaultSchema);

        $resolvedName->fullName(
            $resolvedName->getSchemaName() !== $this->defaultSchema ?
            implode('.', array_reverse($parts)) : $resolvedName->getName()
        );

        return $resolvedName;
    }

    /**
     * Returns all schema names in the database, including the default one but not system schemas.
     *
     * This method should be overridden by child classes to support this feature because the default implementation
     * simply throws an exception.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     *
     * @return array All schemas name in the database, except system schemas.
     */
    protected function findSchemaNames(): array
    {
        $sql = <<<SQL
        SELECT "ns"."nspname"
        FROM "pg_namespace" AS "ns"
        WHERE "ns"."nspname" != 'information_schema' AND "ns"."nspname" NOT LIKE 'pg_%'
        ORDER BY "ns"."nspname" ASC
        SQL;

        return $this->db->createCommand($sql)->queryColumn();
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    protected function findTableComment(TableSchemaInterface $tableSchema): void
    {
        $sql = <<<SQL
        SELECT obj_description(pc.oid, 'pg_class')
        FROM pg_catalog.pg_class pc
        INNER JOIN pg_namespace pn ON pc.relnamespace = pn.oid
        WHERE
        pc.relname=:tableName AND
        pn.nspname=:schemaName
        SQL;

        $comment = $this->db->createCommand($sql, [
            ':schemaName' => $tableSchema->getSchemaName(),
            ':tableName' => $tableSchema->getName(),
        ])->queryScalar();

        $tableSchema->comment(is_string($comment) ? $comment : null);
    }

    /**
     * Returns all table names in the database.
     *
     * This method should be overridden by child classes to support this feature because the default implementation
     * simply throws an exception.
     *
     * @param string $schema The schema of the tables.
     * Defaults to empty string, meaning the current or default schema.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     *
     * @return array All tables name in the database. The names have NO schema name prefix.
     */
    protected function findTableNames(string $schema = ''): array
    {
        if ($schema === '') {
            $schema = $this->defaultSchema;
        }

        $sql = <<<SQL
        SELECT c.relname AS table_name
        FROM pg_class c
        INNER JOIN pg_namespace ns ON ns.oid = c.relnamespace
        WHERE ns.nspname = :schemaName AND c.relkind IN ('r','v','m','f', 'p')
        ORDER BY c.relname
        SQL;

        return $this->db->createCommand($sql, [':schemaName' => $schema])->queryColumn();
    }

    /**
     * Loads the metadata for the specified table.
     *
     * @param string $name The table name.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     *
     * @return TableSchemaInterface|null DBMS-dependent table metadata, `null` if the table doesn't exist.
     */
    protected function loadTableSchema(string $name): TableSchemaInterface|null
    {
        $table = $this->resolveTableName($name);
        $this->findTableComment($table);

        if ($this->findColumns($table)) {
            $this->findConstraints($table);
            return $table;
        }

        return null;
    }

    /**
     * Loads a primary key for the given table.
     *
     * @param string $tableName The table name.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     *
     * @return Constraint|null Primary key for the given table, `null` if the table has no primary key.
     */
    protected function loadTablePrimaryKey(string $tableName): Constraint|null
    {
        $tablePrimaryKey = $this->loadTableConstraints($tableName, self::PRIMARY_KEY);

        return $tablePrimaryKey instanceof Constraint ? $tablePrimaryKey : null;
    }

    /**
     * Loads all foreign keys for the given table.
     *
     * @param string $tableName The table name.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     *
     * @return array Foreign keys for the given table.
     *
     * @psaml-return array|ForeignKeyConstraint[]
     */
    protected function loadTableForeignKeys(string $tableName): array
    {
        $tableForeignKeys = $this->loadTableConstraints($tableName, self::FOREIGN_KEYS);

        return is_array($tableForeignKeys) ? $tableForeignKeys : [];
    }

    /**
     * Loads all indexes for the given table.
     *
     * @param string $tableName The table name.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     *
     * @return IndexConstraint[] Indexes for the given table.
     */
    protected function loadTableIndexes(string $tableName): array
    {
        $sql = <<<SQL
        SELECT
            "ic"."relname" AS "name",
            "ia"."attname" AS "column_name",
            "i"."indisunique" AS "index_is_unique",
            "i"."indisprimary" AS "index_is_primary"
        FROM "pg_class" AS "tc"
        INNER JOIN "pg_namespace" AS "tcns"
            ON "tcns"."oid" = "tc"."relnamespace"
        LEFT JOIN "pg_rewrite" AS "rw"
            ON "tc"."relkind" = 'v' AND "rw"."ev_class" = "tc"."oid" AND "rw"."rulename" = '_RETURN'
        INNER JOIN "pg_index" AS "i"
            ON "i"."indrelid" = "tc"."oid"
                OR "rw"."ev_action" IS NOT NULL
                AND strpos("rw"."ev_action", ':resorigtbl ' || "i"."indrelid" || ' :resorigcol ' || "i"."indkey"[0] || ' ') > 0
        INNER JOIN "pg_class" AS "ic"
            ON "ic"."oid" = "i"."indexrelid"
        INNER JOIN "pg_attribute" AS "ia"
            ON "ia"."attrelid" = "i"."indexrelid" AND "ia"."attnum" <= cardinality("i"."indoption")
        WHERE "tcns"."nspname" = :schemaName AND "tc"."relname" = :tableName
        ORDER BY "i"."indkey", "ia"."attnum" ASC
        SQL;

        $resolvedName = $this->resolveTableName($tableName);
        $indexes = $this->db->createCommand($sql, [
            ':schemaName' => $resolvedName->getSchemaName(),
            ':tableName' => $resolvedName->getName(),
        ])->queryAll();

        /** @psalm-var array[] $indexes */
        $indexes = array_map(array_change_key_case(...), $indexes);
        $indexes = DbArrayHelper::index($indexes, null, ['name']);
        $result = [];

        /**
         * @psalm-var object|string|null $name
         * @psalm-var array<
         *   array-key,
         *   array{
         *     name: string,
         *     column_name: string,
         *     index_is_unique: bool,
         *     index_is_primary: bool
         *   }
         * > $index
         */
        foreach ($indexes as $name => $index) {
            $ic = (new IndexConstraint())
                ->name($name)
                ->columnNames(DbArrayHelper::getColumn($index, 'column_name'))
                ->primary($index[0]['index_is_primary'])
                ->unique($index[0]['index_is_unique']);

            $result[] = $ic;
        }

        return $result;
    }

    /**
     * Loads all unique constraints for the given table.
     *
     * @param string $tableName The table name.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     *
     * @return array Unique constraints for the given table.
     *
     * @psalm-return array|Constraint[]
     */
    protected function loadTableUniques(string $tableName): array
    {
        $tableUniques = $this->loadTableConstraints($tableName, self::UNIQUES);

        return is_array($tableUniques) ? $tableUniques : [];
    }

    /**
     * Loads all check constraints for the given table.
     *
     * @param string $tableName The table name.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     *
     * @return array Check constraints for the given table.
     *
     * @psaml-return array|CheckConstraint[]
     */
    protected function loadTableChecks(string $tableName): array
    {
        $tableChecks = $this->loadTableConstraints($tableName, self::CHECKS);

        return is_array($tableChecks) ? $tableChecks : [];
    }

    /**
     * Loads all default value constraints for the given table.
     *
     * @param string $tableName The table name.
     *
     * @throws NotSupportedException
     *
     * @return DefaultValueConstraint[] Default value constraints for the given table.
     */
    protected function loadTableDefaultValues(string $tableName): array
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported by PostgreSQL.');
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    protected function findViewNames(string $schema = ''): array
    {
        if ($schema === '') {
            $schema = $this->defaultSchema;
        }

        $sql = <<<SQL
        SELECT c.relname AS table_name
        FROM pg_class c
        INNER JOIN pg_namespace ns ON ns.oid = c.relnamespace
        WHERE ns.nspname = :schemaName AND (c.relkind = 'v' OR c.relkind = 'm')
        ORDER BY c.relname
        SQL;

        return $this->db->createCommand($sql, [':schemaName' => $schema])->queryColumn();
    }

    /**
     * Collects the foreign key column details for the given table.
     *
     * @param TableSchemaInterface $table The table metadata
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    protected function findConstraints(TableSchemaInterface $table): void
    {
        /**
         * We need to extract the constraints de hard way since:
         * {@see https://www.postgresql.org/message-id/26677.1086673982@sss.pgh.pa.us}
         */

        $sql = <<<SQL
        SELECT
            ct.conname as constraint_name,
            a.attname as column_name,
            fc.relname as foreign_table_name,
            fns.nspname as foreign_table_schema,
            fa.attname as foreign_column_name
            FROM
            (SELECT ct.conname, ct.conrelid, ct.confrelid, ct.conkey, ct.contype, ct.confkey,
                generate_subscripts(ct.conkey, 1) AS s
                FROM pg_constraint ct
            ) AS ct
            inner join pg_class c on c.oid=ct.conrelid
            inner join pg_namespace ns on c.relnamespace=ns.oid
            inner join pg_attribute a on a.attrelid=ct.conrelid and a.attnum = ct.conkey[ct.s]
            left join pg_class fc on fc.oid=ct.confrelid
            left join pg_namespace fns on fc.relnamespace=fns.oid
            left join pg_attribute fa on fa.attrelid=ct.confrelid and fa.attnum = ct.confkey[ct.s]
        WHERE
            ct.contype='f'
            and c.relname=:tableName
            and ns.nspname=:schemaName
        ORDER BY
            fns.nspname, fc.relname, a.attnum
        SQL;

        /** @psalm-var array{array{tableName: string, columns: array}} $constraints */
        $constraints = [];

        /** @psalm-var array<FindConstraintArray> $rows */
        $rows = $this->db->createCommand($sql, [
            ':schemaName' => $table->getSchemaName(),
            ':tableName' => $table->getName(),
        ])->queryAll();

        foreach ($rows as $constraint) {
            /** @psalm-var FindConstraintArray $constraint */
            $constraint = array_change_key_case($constraint);

            if ($constraint['foreign_table_schema'] !== $this->defaultSchema) {
                $foreignTable = $constraint['foreign_table_schema'] . '.' . $constraint['foreign_table_name'];
            } else {
                $foreignTable = $constraint['foreign_table_name'];
            }

            $name = $constraint['constraint_name'];

            if (!isset($constraints[$name])) {
                $constraints[$name] = [
                    'tableName' => $foreignTable,
                    'columns' => [],
                ];
            }

            $constraints[$name]['columns'][$constraint['column_name']] = $constraint['foreign_column_name'];
        }

        /**
         * @psalm-var int|string $foreingKeyName.
         * @psalm-var array{tableName: string, columns: array} $constraint
         */
        foreach ($constraints as $foreingKeyName => $constraint) {
            $table->foreignKey(
                (string) $foreingKeyName,
                [$constraint['tableName'], ...$constraint['columns']]
            );
        }
    }

    /**
     * Gets information about given table unique indexes.
     *
     * @param TableSchemaInterface $table The table metadata.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     *
     * @return array With index and column names.
     */
    protected function getUniqueIndexInformation(TableSchemaInterface $table): array
    {
        $sql = <<<'SQL'
        SELECT
            i.relname as indexname,
            pg_get_indexdef(idx.indexrelid, k + 1, TRUE) AS columnname
        FROM (
            SELECT *, generate_subscripts(indkey, 1) AS k
            FROM pg_index
        ) idx
        INNER JOIN pg_class i ON i.oid = idx.indexrelid
        INNER JOIN pg_class c ON c.oid = idx.indrelid
        INNER JOIN pg_namespace ns ON c.relnamespace = ns.oid
        WHERE idx.indisprimary = FALSE AND idx.indisunique = TRUE
        AND c.relname = :tableName AND ns.nspname = :schemaName
        ORDER BY i.relname, k
        SQL;

        return $this->db->createCommand($sql, [
            ':schemaName' => $table->getSchemaName(),
            ':tableName' => $table->getName(),
        ])->queryAll();
    }

    /**
     * Returns all unique indexes for the given table.
     *
     * Each array element is of the following structure:
     *
     * ```php
     * [
     *     'IndexName1' => ['col1' [, ...]],
     *     'IndexName2' => ['col2' [, ...]],
     * ]
     * ```
     *
     * @param TableSchemaInterface $table The table metadata
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     *
     * @return array All unique indexes for the given table.
     */
    public function findUniqueIndexes(TableSchemaInterface $table): array
    {
        $uniqueIndexes = [];

        /** @psalm-var array{indexname: string, columnname: string} $row */
        foreach ($this->getUniqueIndexInformation($table) as $row) {
            /** @psalm-var array{indexname: string, columnname: string} $row */
            $row = array_change_key_case($row);

            $column = $row['columnname'];

            if (str_starts_with($column, '"') && str_ends_with($column, '"')) {
                /**
                 * postgres will quote names that aren't lowercase-only.
                 *
                 * {@see https://github.com/yiisoft/yii2/issues/10613}
                 */
                $column = substr($column, 1, -1);
            }

            $uniqueIndexes[$row['indexname']][] = $column;
        }

        return $uniqueIndexes;
    }

    /**
     * Collects the metadata of table columns.
     *
     * @param TableSchemaInterface $table The table metadata.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws JsonException
     * @throws Throwable
     *
     * @return bool Whether the table exists in the database.
     */
    protected function findColumns(TableSchemaInterface $table): bool
    {
        $orIdentity = '';

        if (version_compare($this->db->getServerInfo()->getVersion(), '12.0', '>=')) {
            $orIdentity = 'OR a.attidentity != \'\'';
        }

        $sql = <<<SQL
        SELECT
            a.attname AS column_name,
            COALESCE(td.typname, tb.typname, t.typname) AS data_type,
            COALESCE(td.typtype, tb.typtype, t.typtype) AS type_type,
            (SELECT nspname FROM pg_namespace WHERE oid = COALESCE(td.typnamespace, tb.typnamespace, t.typnamespace)) AS type_scheme,
            a.attlen AS character_maximum_length,
            pg_catalog.col_description(c.oid, a.attnum) AS column_comment,
            NOT (a.attnotnull OR t.typnotnull) AS is_nullable,
            COALESCE(t.typdefault, pg_get_expr(ad.adbin, ad.adrelid)) AS column_default,
            COALESCE(pg_get_expr(ad.adbin, ad.adrelid) ~ 'nextval', false) $orIdentity AS is_autoinc,
            pg_get_serial_sequence(quote_ident(d.nspname) || '.' || quote_ident(c.relname), a.attname)
            AS sequence_name,
            CASE WHEN COALESCE(td.typtype, tb.typtype, t.typtype) = 'e'::char
                THEN array_to_string(
                    (
                        SELECT array_agg(enumlabel)
                        FROM pg_enum
                        WHERE enumtypid = COALESCE(td.oid, tb.oid, a.atttypid)
                    )::varchar[],
                ',')
                ELSE NULL
            END AS enum_values,
            COALESCE(
                information_schema._pg_char_max_length(
                    COALESCE(td.oid, tb.oid, a.atttypid),
                    a.atttypmod
                ),
                information_schema._pg_datetime_precision(
                    COALESCE(td.oid, tb.oid, a.atttypid),
                    a.atttypmod
                ),
                CASE a.atttypmod
                    WHEN -1 THEN null
                    ELSE ((a.atttypmod - 4) >> 16) & 65535
                END
            ) AS size,
            information_schema._pg_numeric_scale(
                COALESCE(td.oid, tb.oid, a.atttypid),
                a.atttypmod
            ) AS scale,
            ct.contype,
            COALESCE(NULLIF(a.attndims, 0), NULLIF(t.typndims, 0), (t.typcategory='A')::int) AS dimension
        FROM
            pg_class c
            LEFT JOIN pg_attribute a ON a.attrelid = c.oid
            LEFT JOIN pg_attrdef ad ON a.attrelid = ad.adrelid AND a.attnum = ad.adnum
            LEFT JOIN pg_type t ON a.atttypid = t.oid
            LEFT JOIN pg_type tb ON (a.attndims > 0 OR t.typcategory='A') AND t.typelem > 0 AND t.typelem = tb.oid
                OR t.typbasetype > 0 AND t.typbasetype = tb.oid
            LEFT JOIN pg_type td ON t.typndims > 0 AND t.typbasetype > 0 AND tb.typelem = td.oid
            LEFT JOIN pg_namespace d ON d.oid = c.relnamespace
            LEFT JOIN pg_rewrite rw ON c.relkind = 'v' AND rw.ev_class = c.oid AND rw.rulename = '_RETURN'
            LEFT JOIN pg_constraint ct ON (ct.contype = 'p' OR ct.contype = 'u' AND cardinality(ct.conkey) = 1)
                AND (
                    ct.conrelid = c.oid AND a.attnum = ANY (ct.conkey)
                    OR rw.ev_action IS NOT NULL AND ct.conrelid != 0
                    AND strpos(rw.ev_action, ':resorigtbl ' || ct.conrelid || ' ') > 0
                    AND rw.ev_action ~ ('.* :resno ' || a.attnum || ' :resname \S+ :ressortgroupref \d+ :resorigtbl '
                        || ct.conrelid || ' :resorigcol (?:'
                        || replace(substr(ct.conkey::text, 2, length(ct.conkey::text) - 2), ',', '|') || ') .*')
                )
        WHERE
            a.attnum > 0 AND t.typname != '' AND NOT a.attisdropped
            AND c.relname = :tableName
            AND d.nspname = :schemaName
        ORDER BY
            a.attnum;
        SQL;

        $schemaName = $table->getSchemaName();
        $tableName = $table->getName();

        $columns = $this->db->createCommand($sql, [
            ':schemaName' => $schemaName,
            ':tableName' => $tableName,
        ])->queryAll();

        if (empty($columns)) {
            return false;
        }

        /** @psalm-var ColumnArray $info */
        foreach ($columns as $info) {
            $info = array_change_key_case($info);

            $info['schema'] = $schemaName;
            $info['table'] = $tableName;

            /** @psalm-var ColumnArray $info */
            $column = $this->loadColumn($info);

            $table->column($info['column_name'], $column);

            if ($column->isPrimaryKey()) {
                $table->primaryKey($info['column_name']);

                if ($column instanceof SequenceColumnInterface && $table->getSequenceName() === null) {
                    $table->sequenceName($column->getSequenceName());
                }
            }
        }

        return true;
    }

    /**
     * Loads the column information into a {@see ColumnInterface} object.
     *
     * @psalm-param ColumnArray $info Column information.
     *
     * @return ColumnInterface The column object.
     */
    private function loadColumn(array $info): ColumnInterface
    {
        $columnFactory = $this->getColumnFactory();
        $dbType = $info['data_type'];

        if (!in_array($info['type_scheme'], [$this->defaultSchema, 'pg_catalog'], true)) {
            $dbType = $info['type_scheme'] . '.' . $dbType;
        }

        $columnInfo = [
            'autoIncrement' => (bool) $info['is_autoinc'],
            'comment' => $info['column_comment'],
            'dbType' => $dbType,
            'enumValues' => $info['enum_values'] !== null
                ? explode(',', str_replace(["''"], ["'"], $info['enum_values']))
                : null,
            'name' => $info['column_name'],
            'notNull' => !$info['is_nullable'],
            'primaryKey' => $info['contype'] === 'p',
            'scale' => $info['scale'] !== null ? (int) $info['scale'] : null,
            'schema' => $info['schema'],
            'size' => $info['size'] !== null ? (int) $info['size'] : null,
            'table' => $info['table'],
            'unique' => $info['contype'] === 'u',
        ];

        if ($info['type_type'] === 'c') {
            $structured = $this->resolveTableName($dbType);

            if ($this->findColumns($structured)) {
                $columnInfo['columns'] = $structured->getColumns();
            }

            $columnInfo['type'] = ColumnType::STRUCTURED;
        }

        $dimension = (int) $info['dimension'];

        if ($dimension > 0) {
            $columnInfo['column'] = $columnFactory->fromDbType($dbType, $columnInfo);
            $columnInfo['dimension'] = $dimension;
            $columnInfo['defaultValueRaw'] = $info['column_default'];

            return $columnFactory->fromType(ColumnType::ARRAY, $columnInfo);
        }

        $defaultValue = $info['column_default'];

        /**
         * pg_get_serial_sequence() doesn't track DEFAULT value change.
         * GENERATED BY IDENTITY columns always have a null default value.
         */
        if ($defaultValue !== null && preg_match("/^nextval\('([^']+)/", $defaultValue, $matches) === 1) {
            $defaultValue = null;
            $columnInfo['sequenceName'] = $matches[1];
        } elseif ($info['sequence_name'] !== null) {
            $columnInfo['sequenceName'] = $this->resolveTableName($info['sequence_name'])->getFullName();
        }

        $columnInfo['defaultValueRaw'] = $defaultValue;

        /** @psalm-suppress InvalidArgument */
        return $columnFactory->fromDbType($dbType, $columnInfo);
    }

    /**
     * Loads multiple types of constraints and returns the specified ones.
     *
     * @param string $tableName The table name.
     * @param string $returnType The return type:
     * - primaryKey
     * - foreignKeys
     * - uniques
     * - checks
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     *
     * @return array|Constraint|null Constraints.
     *
     * @psalm-return CheckConstraint[]|Constraint[]|ForeignKeyConstraint[]|Constraint|null
     */
    private function loadTableConstraints(string $tableName, string $returnType): array|Constraint|null
    {
        $sql = <<<SQL
        SELECT
            "c"."conname" AS "name",
            "a"."attname" AS "column_name",
            "c"."contype" AS "type",
            "ftcns"."nspname" AS "foreign_table_schema",
            "ftc"."relname" AS "foreign_table_name",
            "fa"."attname" AS "foreign_column_name",
            "c"."confupdtype" AS "on_update",
            "c"."confdeltype" AS "on_delete",
            pg_get_constraintdef("c"."oid") AS "check_expr"
        FROM "pg_class" AS "tc"
        INNER JOIN "pg_namespace" AS "tcns"
            ON "tcns"."oid" = "tc"."relnamespace"
        INNER JOIN "pg_attribute" AS "a"
            ON "a"."attrelid" = "tc"."oid"
        LEFT JOIN pg_rewrite AS rw
            ON "tc"."relkind" = 'v' AND "rw"."ev_class" = "tc"."oid" AND "rw"."rulename" = '_RETURN'
        INNER JOIN "pg_constraint" AS "c"
            ON "c"."conrelid" = "tc"."oid" AND "a"."attnum" = ANY ("c"."conkey")
                OR "rw"."ev_action" IS NOT NULL AND "c"."conrelid" != 0
                AND strpos("rw"."ev_action", ':resorigtbl ' || "c"."conrelid" || ' ') > 0
                AND "rw"."ev_action" ~ ('.* :resno ' || "a"."attnum" || ' :resname \S+ :ressortgroupref \d+ :resorigtbl '
                    || "c"."conrelid" || ' :resorigcol (?:'
                    || replace(substr("c"."conkey"::text, 2, length("c"."conkey"::text) - 2), ',', '|') || ') .*')
        LEFT JOIN "pg_class" AS "ftc"
            ON "ftc"."oid" = "c"."confrelid"
        LEFT JOIN "pg_namespace" AS "ftcns"
            ON "ftcns"."oid" = "ftc"."relnamespace"
        LEFT JOIN "pg_attribute" "fa"
            ON "fa"."attrelid" = "c"."confrelid" AND "fa"."attnum" = ANY ("c"."confkey")
        WHERE "tcns"."nspname" = :schemaName AND "tc"."relname" = :tableName
        ORDER BY "a"."attnum" ASC, "fa"."attnum" ASC
        SQL;

        /** @psalm-var string[] $actionTypes */
        $actionTypes = [
            'a' => 'NO ACTION',
            'r' => 'RESTRICT',
            'c' => 'CASCADE',
            'n' => 'SET NULL',
            'd' => 'SET DEFAULT',
        ];

        $resolvedName = $this->resolveTableName($tableName);
        $constraints = $this->db->createCommand($sql, [
            ':schemaName' => $resolvedName->getSchemaName(),
            ':tableName' => $resolvedName->getName(),
        ])->queryAll();

        /** @psalm-var array[][] $constraints */
        $constraints = array_map(array_change_key_case(...), $constraints);
        $constraints = DbArrayHelper::index($constraints, null, ['type', 'name']);

        $result = [
            self::PRIMARY_KEY => null,
            self::FOREIGN_KEYS => [],
            self::UNIQUES => [],
            self::CHECKS => [],
        ];

        /**
         * @psalm-var string $type
         * @psalm-var array $names
         */
        foreach ($constraints as $type => $names) {
            /**
             * @psalm-var object|string|null $name
             * @psalm-var ConstraintArray $constraint
             */
            foreach ($names as $name => $constraint) {
                switch ($type) {
                    case 'p':
                        $result[self::PRIMARY_KEY] = (new Constraint())
                            ->name($name)
                            ->columnNames(DbArrayHelper::getColumn($constraint, 'column_name'));
                        break;
                    case 'f':
                        $onDelete = $actionTypes[$constraint[0]['on_delete']] ?? null;
                        $onUpdate = $actionTypes[$constraint[0]['on_update']] ?? null;

                        $result[self::FOREIGN_KEYS][] = (new ForeignKeyConstraint())
                            ->name($name)
                            ->columnNames(array_values(
                                array_unique(DbArrayHelper::getColumn($constraint, 'column_name'))
                            ))
                            ->foreignSchemaName($constraint[0]['foreign_table_schema'])
                            ->foreignTableName($constraint[0]['foreign_table_name'])
                            ->foreignColumnNames(array_values(
                                array_unique(DbArrayHelper::getColumn($constraint, 'foreign_column_name'))
                            ))
                            ->onDelete($onDelete)
                            ->onUpdate($onUpdate);
                        break;
                    case 'u':
                        $result[self::UNIQUES][] = (new Constraint())
                            ->name($name)
                            ->columnNames(DbArrayHelper::getColumn($constraint, 'column_name'));
                        break;
                    case 'c':
                        $result[self::CHECKS][] = (new CheckConstraint())
                            ->name($name)
                            ->columnNames(DbArrayHelper::getColumn($constraint, 'column_name'))
                            ->expression($constraint[0]['check_expr']);
                        break;
                }
            }
        }

        foreach ($result as $type => $data) {
            $this->setTableMetadata($tableName, $type, $data);
        }

        return $result[$returnType];
    }

    /**
     * Returns the cache key for the specified table name.
     *
     * @param string $name The table name.
     *
     * @return array The cache key.
     */
    protected function getCacheKey(string $name): array
    {
        return [self::class, ...$this->generateCacheKey(), $this->db->getQuoter()->getRawTableName($name)];
    }

    /**
     * Returns the cache tag name.
     *
     * This allows {@see refresh()} to invalidate all cached table schemas.
     *
     * @return string The cache tag name.
     */
    protected function getCacheTag(): string
    {
        return md5(serialize([self::class, ...$this->generateCacheKey()]));
    }
}
