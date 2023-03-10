<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql;

use JsonException;
use Throwable;
use Yiisoft\Db\Constraint\CheckConstraint;
use Yiisoft\Db\Constraint\Constraint;
use Yiisoft\Db\Constraint\DefaultValueConstraint;
use Yiisoft\Db\Constraint\ForeignKeyConstraint;
use Yiisoft\Db\Constraint\IndexConstraint;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Helper\ArrayHelper;
use Yiisoft\Db\Schema\AbstractSchema;
use Yiisoft\Db\Schema\Builder\ColumnInterface;
use Yiisoft\Db\Schema\ColumnSchemaInterface;
use Yiisoft\Db\Schema\TableSchemaInterface;

use function array_merge;
use function array_unique;
use function array_values;
use function bindec;
use function explode;
use function preg_match;
use function preg_replace;
use function str_replace;
use function substr;

/**
 * Implements the PostgreSQL Server specific schema, supporting PostgreSQL Server version 9.6 and above.
 *
 * @psalm-type ColumnArray = array{
 *   table_schema: string,
 *   table_name: string,
 *   column_name: string,
 *   data_type: string,
 *   type_type: string|null,
 *   type_scheme: string|null,
 *   character_maximum_length: int,
 *   column_comment: string|null,
 *   modifier: int,
 *   is_nullable: bool,
 *   column_default: mixed,
 *   is_autoinc: bool,
 *   sequence_name: string|null,
 *   enum_values: array<array-key, float|int|string>|string|null,
 *   numeric_precision: int|null,
 *   numeric_scale: int|null,
 *   size: string|null,
 *   is_pkey: bool|null,
 *   dimension: int
 * }
 *
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
 *
 * @psalm-type FindConstraintArray = array{
 *   constraint_name: string,
 *   column_name: string,
 *   foreign_table_name: string,
 *   foreign_table_schema: string,
 *   foreign_column_name: string,
 * }
 */
final class Schema extends AbstractSchema
{
    /**
     * @var array The mapping from physical column types (keys) to abstract column types (values).
     *
     * @link https://www.postgresql.org/docs/current/static/datatype.html#DATATYPE-TABLE
     *
     * @psalm-var string[]
     */
    private array $typeMap = [
        'bit' => self::TYPE_INTEGER,
        'bit varying' => self::TYPE_INTEGER,
        'varbit' => self::TYPE_INTEGER,
        'bool' => self::TYPE_BOOLEAN,
        'boolean' => self::TYPE_BOOLEAN,
        'box' => self::TYPE_STRING,
        'circle' => self::TYPE_STRING,
        'point' => self::TYPE_STRING,
        'line' => self::TYPE_STRING,
        'lseg' => self::TYPE_STRING,
        'polygon' => self::TYPE_STRING,
        'path' => self::TYPE_STRING,
        'character' => self::TYPE_CHAR,
        'char' => self::TYPE_CHAR,
        'bpchar' => self::TYPE_CHAR,
        'character varying' => self::TYPE_STRING,
        'varchar' => self::TYPE_STRING,
        'text' => self::TYPE_TEXT,
        'bytea' => self::TYPE_BINARY,
        'cidr' => self::TYPE_STRING,
        'inet' => self::TYPE_STRING,
        'macaddr' => self::TYPE_STRING,
        'real' => self::TYPE_FLOAT,
        'float4' => self::TYPE_FLOAT,
        'double precision' => self::TYPE_DOUBLE,
        'float8' => self::TYPE_DOUBLE,
        'decimal' => self::TYPE_DECIMAL,
        'numeric' => self::TYPE_DECIMAL,
        'money' => self::TYPE_MONEY,
        'smallint' => self::TYPE_SMALLINT,
        'int2' => self::TYPE_SMALLINT,
        'int4' => self::TYPE_INTEGER,
        'int' => self::TYPE_INTEGER,
        'integer' => self::TYPE_INTEGER,
        'bigint' => self::TYPE_BIGINT,
        'int8' => self::TYPE_BIGINT,
        'oid' => self::TYPE_BIGINT, // shouldn't be used. it's pg internal!
        'smallserial' => self::TYPE_SMALLINT,
        'serial2' => self::TYPE_SMALLINT,
        'serial4' => self::TYPE_INTEGER,
        'serial' => self::TYPE_INTEGER,
        'bigserial' => self::TYPE_BIGINT,
        'serial8' => self::TYPE_BIGINT,
        'pg_lsn' => self::TYPE_BIGINT,
        'date' => self::TYPE_DATE,
        'interval' => self::TYPE_STRING,
        'time without time zone' => self::TYPE_TIME,
        'time' => self::TYPE_TIME,
        'time with time zone' => self::TYPE_TIME,
        'timetz' => self::TYPE_TIME,
        'timestamp without time zone' => self::TYPE_TIMESTAMP,
        'timestamp' => self::TYPE_TIMESTAMP,
        'timestamp with time zone' => self::TYPE_TIMESTAMP,
        'timestamptz' => self::TYPE_TIMESTAMP,
        'abstime' => self::TYPE_TIMESTAMP,
        'tsquery' => self::TYPE_STRING,
        'tsvector' => self::TYPE_STRING,
        'txid_snapshot' => self::TYPE_STRING,
        'unknown' => self::TYPE_STRING,
        'uuid' => self::TYPE_STRING,
        'json' => self::TYPE_JSON,
        'jsonb' => self::TYPE_JSON,
        'xml' => self::TYPE_STRING,
    ];

    /**
     * @var string|null The default schema used for the current session.
     */
    protected string|null $defaultSchema = 'public';

    /**
     * @var string|string[] Character used to quote schema, table, etc. names.
     *
     * An array of 2 characters can be used in case starting and ending characters are different.
     */
    protected string|array $tableQuoteCharacter = '"';

    public function createColumn(string $type, array|int|string $length = null): ColumnInterface
    {
        return new Column($type, $length);
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
        INNER JOIN "pg_index" AS "i"
            ON "i"."indrelid" = "tc"."oid"
        INNER JOIN "pg_class" AS "ic"
            ON "ic"."oid" = "i"."indexrelid"
        INNER JOIN "pg_attribute" AS "ia"
            ON "ia"."attrelid" = "i"."indexrelid"
        WHERE "tcns"."nspname" = :schemaName AND "tc"."relname" = :tableName
        ORDER BY "ia"."attnum" ASC
        SQL;

        $resolvedName = $this->resolveTableName($tableName);
        $indexes = $this->db->createCommand($sql, [
            ':schemaName' => $resolvedName->getSchemaName(),
            ':tableName' => $resolvedName->getName(),
        ])->queryAll();

        /** @psalm-var array[] $indexes */
        $indexes = $this->normalizeRowKeyCase($indexes, true);
        $indexes = ArrayHelper::index($indexes, null, ['name']);
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
                ->columnNames(ArrayHelper::getColumn($index, 'column_name'))
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

        /**
         * @psalm-var array<
         *   array{
         *     constraint_name: string,
         *     column_name: string,
         *     foreign_table_name: string,
         *     foreign_table_schema: string,
         *     foreign_column_name: string,
         *   }
         * > $rows
         */
        $rows = $this->db->createCommand($sql, [
            ':schemaName' => $table->getSchemaName(),
            ':tableName' => $table->getName(),
        ])->queryAll();

        foreach ($rows as $constraint) {
            /** @psalm-var array{
             *     constraint_name: string,
             *     column_name: string,
             *     foreign_table_name: string,
             *     foreign_table_schema: string,
             *     foreign_column_name: string,
             *   } $constraint */
            $constraint = $this->normalizeRowKeyCase($constraint, false);

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
                array_merge([$constraint['tableName']], $constraint['columns'])
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
            $row = $this->normalizeRowKeyCase($row, false);

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

        if (version_compare($this->db->getServerVersion(), '12.0', '>=')) {
            $orIdentity = 'OR a.attidentity != \'\'';
        }

        $sql = <<<SQL
        SELECT
            d.nspname AS table_schema,
            c.relname AS table_name,
            a.attname AS column_name,
            COALESCE(td.typname, tb.typname, t.typname) AS data_type,
            COALESCE(td.typtype, tb.typtype, t.typtype) AS type_type,
            (SELECT nspname FROM pg_namespace WHERE oid = COALESCE(td.typnamespace, tb.typnamespace, t.typnamespace)) AS type_scheme,
            a.attlen AS character_maximum_length,
            pg_catalog.col_description(c.oid, a.attnum) AS column_comment,
            a.atttypmod AS modifier,
            a.attnotnull = false AS is_nullable,
            CAST(pg_get_expr(ad.adbin, ad.adrelid) AS varchar) AS column_default,
            coalesce(pg_get_expr(ad.adbin, ad.adrelid) ~ 'nextval',false) $orIdentity AS is_autoinc,
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
            CASE atttypid
                WHEN 21 /*int2*/ THEN 16
                WHEN 23 /*int4*/ THEN 32
                WHEN 20 /*int8*/ THEN 64
                WHEN 1700 /*numeric*/ THEN
                    CASE WHEN atttypmod = -1
                        THEN null
                        ELSE ((atttypmod - 4) >> 16) & 65535
                        END
                WHEN 700 /*float4*/ THEN 24 /*FLT_MANT_DIG*/
                WHEN 701 /*float8*/ THEN 53 /*DBL_MANT_DIG*/
                    ELSE null
                    END   AS numeric_precision,
            CASE
                WHEN atttypid IN (21, 23, 20) THEN 0
                WHEN atttypid IN (1700) THEN
            CASE
                WHEN atttypmod = -1 THEN null
                    ELSE (atttypmod - 4) & 65535
                    END
                    ELSE null
                    END AS numeric_scale,
                    CAST(
                        information_schema._pg_char_max_length(
                        information_schema._pg_truetypid(a, t),
                        information_schema._pg_truetypmod(a, t)
                        ) AS numeric
                    ) AS size,
                    a.attnum = any (ct.conkey) as is_pkey,
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
                LEFT JOIN pg_constraint ct ON ct.conrelid = c.oid AND ct.contype = 'p'
            WHERE
                a.attnum > 0 AND t.typname != '' AND NOT a.attisdropped
                AND c.relname = :tableName
                AND d.nspname = :schemaName
            ORDER BY
                a.attnum;
        SQL;

        $columns = $this->db->createCommand($sql, [
            ':schemaName' => $table->getSchemaName(),
            ':tableName' => $table->getName(),
        ])->queryAll();

        if (empty($columns)) {
            return false;
        }

        /** @psalm-var array $column */
        foreach ($columns as $column) {
            /** @psalm-var ColumnArray $column */
            $column = $this->normalizeRowKeyCase($column, false);

            /** @psalm-var ColumnSchema $loadColumnSchema */
            $loadColumnSchema = $this->loadColumnSchema($column);

            $table->columns($loadColumnSchema->getName(), $loadColumnSchema);

            /** @psalm-var mixed $defaultValue */
            $defaultValue = $loadColumnSchema->getDefaultValue();

            if ($loadColumnSchema->isPrimaryKey()) {
                $table->primaryKey($loadColumnSchema->getName());

                if ($table->getSequenceName() === null) {
                    $table->sequenceName($loadColumnSchema->getSequenceName());
                }

                $loadColumnSchema->defaultValue(null);
            } elseif ($defaultValue) {
                if (
                    is_string($defaultValue) &&
                    in_array(
                        $loadColumnSchema->getType(),
                        [self::TYPE_TIMESTAMP, self::TYPE_DATE, self::TYPE_TIME],
                        true
                    ) &&
                    in_array(
                        strtoupper($defaultValue),
                        ['NOW()', 'CURRENT_TIMESTAMP', 'CURRENT_DATE', 'CURRENT_TIME'],
                        true
                    )
                ) {
                    $loadColumnSchema->defaultValue(new Expression($defaultValue));
                } elseif ($loadColumnSchema->getType() === 'boolean') {
                    $loadColumnSchema->defaultValue(($defaultValue  === 'true'));
                } elseif (is_string($defaultValue) && preg_match("/^B'(.*?)'::/", $defaultValue, $matches)) {
                    $loadColumnSchema->defaultValue(bindec($matches[1]));
                } elseif (is_string($defaultValue) && preg_match("/^'(\d+)'::\"bit\"$/", $defaultValue, $matches)) {
                    $loadColumnSchema->defaultValue(bindec($matches[1]));
                } elseif (is_string($defaultValue) && preg_match("/^'(.*?)'::/", $defaultValue, $matches)) {
                    $loadColumnSchema->defaultValue($loadColumnSchema->phpTypecast($matches[1]));
                } elseif (
                    is_string($defaultValue) &&
                    preg_match('/^(\()?(.*?)(?(1)\))(?:::.+)?$/', $defaultValue, $matches)
                ) {
                    if ($matches[2] === 'NULL') {
                        $loadColumnSchema->defaultValue(null);
                    } else {
                        $loadColumnSchema->defaultValue($loadColumnSchema->phpTypecast($matches[2]));
                    }
                } else {
                    $loadColumnSchema->defaultValue($loadColumnSchema->phpTypecast($defaultValue));
                }
            }
        }

        return true;
    }

    /**
     * Loads the column information into a {@see ColumnSchemaInterface} object.
     *
     * @psalm-param array{
     *   table_schema: string,
     *   table_name: string,
     *   column_name: string,
     *   data_type: string,
     *   type_type: string|null,
     *   type_scheme: string|null,
     *   character_maximum_length: int,
     *   column_comment: string|null,
     *   modifier: int,
     *   is_nullable: bool,
     *   column_default: mixed,
     *   is_autoinc: bool,
     *   sequence_name: string|null,
     *   enum_values: array<array-key, float|int|string>|string|null,
     *   numeric_precision: int|null,
     *   numeric_scale: int|null,
     *   size: string|null,
     *   is_pkey: bool|null,
     *   dimension: int
     * } $info Column information.
     *
     * @return ColumnSchemaInterface The column schema object.
     */
    protected function loadColumnSchema(array $info): ColumnSchemaInterface
    {
        $column = $this->createColumnSchema();
        $column->allowNull($info['is_nullable']);
        $column->autoIncrement($info['is_autoinc']);
        $column->comment($info['column_comment']);

        if (!in_array($info['type_scheme'], [$this->defaultSchema, 'pg_catalog'], true)
        ) {
            $column->dbType($info['type_scheme'] . '.' . $info['data_type']);
        } else {
            $column->dbType($info['data_type']);
        }

        $column->defaultValue($info['column_default']);
        $column->enumValues(($info['enum_values'] !== null)
            ? explode(',', str_replace(["''"], ["'"], $info['enum_values'])) : null);
        $column->unsigned(false); // has no meaning in PG
        $column->primaryKey((bool) $info['is_pkey']);
        $column->name($info['column_name']);
        $column->precision($info['numeric_precision']);
        $column->scale($info['numeric_scale']);
        $column->size($info['size'] === null ? null : (int) $info['size']);
        $column->dimension($info['dimension']);

        /**
         * pg_get_serial_sequence() doesn't track DEFAULT value change.
         *
         * GENERATED BY IDENTITY columns always have a null default value.
         *
         * @psalm-var mixed $defaultValue
         */
        $defaultValue = $column->getDefaultValue();
        $sequenceName = $info['sequence_name'] ?? null;

        if (
            isset($defaultValue) &&
            is_string($defaultValue) &&
            preg_match("/nextval\\('\"?\\w+\"?\.?\"?\\w+\"?'(::regclass)?\\)/", $defaultValue) === 1
        ) {
            $column->sequenceName(preg_replace(
                ['/nextval/', '/::/', '/regclass/', '/\'\)/', '/\(\'/'],
                '',
                $defaultValue
            ));
        } elseif ($sequenceName !== null) {
            $column->sequenceName($this->resolveTableName($sequenceName)->getFullName());
        }

        if (isset($this->typeMap[$column->getDbType()])) {
            $column->type($this->typeMap[$column->getDbType()]);
        } else {
            $column->type(self::TYPE_STRING);
        }

        $column->phpType($this->getColumnPhpType($column));

        return $column;
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
        INNER JOIN "pg_constraint" AS "c"
            ON "c"."conrelid" = "tc"."oid"
        INNER JOIN "pg_attribute" AS "a"
            ON "a"."attrelid" = "c"."conrelid" AND "a"."attnum" = ANY ("c"."conkey")
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
        $constraints = $this->normalizeRowKeyCase($constraints, true);
        $constraints = ArrayHelper::index($constraints, null, ['type', 'name']);

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
                            ->columnNames(ArrayHelper::getColumn($constraint, 'column_name'));
                        break;
                    case 'f':
                        $onDelete = $actionTypes[$constraint[0]['on_delete']] ?? null;
                        $onUpdate = $actionTypes[$constraint[0]['on_update']] ?? null;

                        $result[self::FOREIGN_KEYS][] = (new ForeignKeyConstraint())
                            ->name($name)
                            ->columnNames(array_values(
                                array_unique(ArrayHelper::getColumn($constraint, 'column_name'))
                            ))
                            ->foreignSchemaName($constraint[0]['foreign_table_schema'])
                            ->foreignTableName($constraint[0]['foreign_table_name'])
                            ->foreignColumnNames(array_values(
                                array_unique(ArrayHelper::getColumn($constraint, 'foreign_column_name'))
                            ))
                            ->onDelete($onDelete)
                            ->onUpdate($onUpdate);
                        break;
                    case 'u':
                        $result[self::UNIQUES][] = (new Constraint())
                            ->name($name)
                            ->columnNames(ArrayHelper::getColumn($constraint, 'column_name'));
                        break;
                    case 'c':
                        $result[self::CHECKS][] = (new CheckConstraint())
                            ->name($name)
                            ->columnNames(ArrayHelper::getColumn($constraint, 'column_name'))
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
     * Creates a column schema for the database.
     *
     * This method may be overridden by child classes to create a DBMS-specific column schema.
     */
    private function createColumnSchema(): ColumnSchema
    {
        return new ColumnSchema();
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
        return array_merge([self::class], $this->db->getCacheKey(), [$this->getRawTableName($name)]);
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
        return md5(serialize(array_merge([self::class], $this->db->getCacheKey())));
    }
}
