<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql;

use JsonException;
use Throwable;
use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Constraint\CheckConstraint;
use Yiisoft\Db\Constraint\Constraint;
use Yiisoft\Db\Constraint\DefaultValueConstraint;
use Yiisoft\Db\Constraint\ForeignKeyConstraint;
use Yiisoft\Db\Constraint\IndexConstraint;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Schema\ColumnSchemaBuilder;
use Yiisoft\Db\Schema\Schema as AbstractSchema;
use Yiisoft\Db\Schema\TableSchemaInterface;

use function array_change_key_case;
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
 * The class Schema is the class for retrieving metadata from a PostgreSQL database
 * (version 9.6 and above).
 *
 * @psalm-type ColumnArray = array{
 *   table_schema: string,
 *   table_name: string,
 *   column_name: string,
 *   data_type: string,
 *   type_type: string|null,
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
    public const TYPE_JSONB = 'jsonb';

    /**
     * @var array The mapping from physical column types (keys) to abstract column types (values).
     *
     * {@see http://www.postgresql.org/docs/current/static/datatype.html#DATATYPE-TABLE}
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
        'oid' => self::TYPE_BIGINT, // should not be used. it's pg internal!
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

    private array $viewNames = [];

    public function __construct(private ConnectionInterface $db, SchemaCache $schemaCache)
    {
        parent::__construct($schemaCache);
    }

    /**
     * @var string|null the default schema used for the current session.
     */
    protected ?string $defaultSchema = 'public';

    /**
     * @var string|string[] character used to quote schema, table, etc. names. An array of 2 characters can be used in
     * case starting and ending characters are different.
     */
    protected string|array $tableQuoteCharacter = '"';

    /**
     * Resolves the table name and schema name (if any).
     *
     * @param string $name the table name.
     *
     * @return TableSchema with resolved table, schema, etc. names.
     *
     * {@see TableSchema}
     */
    protected function resolveTableName(string $name): TableSchema
    {
        $resolvedName = new TableSchema();

        $parts = explode('.', str_replace('"', '', $name));

        if (isset($parts[1])) {
            $resolvedName->schemaName($parts[0]);
            $resolvedName->name($parts[1]);
        } else {
            $resolvedName->schemaName($this->defaultSchema);
            $resolvedName->name($name);
        }

        $resolvedName->fullName(
            (
                $resolvedName->getSchemaName() !== $this->defaultSchema ?
                    (string) $resolvedName->getSchemaName() . '.' :
                    ''
            ) . $resolvedName->getName()
        );

        return $resolvedName;
    }

    /**
     * Returns all schema names in the database, including the default one but not system schemas.
     *
     * This method should be overridden by child classes in order to support this feature because the default
     * implementation simply throws an exception.
     *
     * @throws Exception|InvalidConfigException|Throwable
     *
     * @return array all schema names in the database, except system schemas.
     */
    protected function findSchemaNames(): array
    {
        $sql = <<<SQL
        SELECT "ns"."nspname"
        FROM "pg_namespace" AS "ns"
        WHERE "ns"."nspname" != 'information_schema' AND "ns"."nspname" NOT LIKE 'pg_%'
        ORDER BY "ns"."nspname" ASC
        SQL;

        $schemaNames = $this->db->createCommand($sql)->queryColumn();
        if (!$schemaNames) {
            return [];
        }

        return $schemaNames;
    }

    /**
     * Returns all table names in the database.
     *
     * This method should be overridden by child classes in order to support this feature because the default
     * implementation simply throws an exception.
     *
     * @param string $schema the schema of the tables. Defaults to empty string, meaning the current or default schema.
     *
     * @throws Exception|InvalidConfigException|Throwable
     *
     * @return array all table names in the database. The names have NO schema name prefix.
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

        $tableNames = $this->db->createCommand($sql, [':schemaName' => $schema])->queryColumn();
        if (!$tableNames) {
            return [];
        }

        return $tableNames;
    }

    /**
     * Loads the metadata for the specified table.
     *
     * @param string $name table name.
     *
     * @throws Exception|InvalidConfigException|Throwable
     *
     * @return TableSchema|null DBMS-dependent table metadata, `null` if the table does not exist.
     */
    protected function loadTableSchema(string $name): ?TableSchema
    {
        $table = new TableSchema();

        $this->resolveTableNames($table, $name);

        if ($this->findColumns($table)) {
            $this->findConstraints($table);
            return $table;
        }

        return null;
    }

    /**
     * Loads a primary key for the given table.
     *
     * @param string $tableName table name.
     *
     * @throws Exception|InvalidConfigException|Throwable
     *
     * @return Constraint|null primary key for the given table, `null` if the table has no primary key.
     */
    protected function loadTablePrimaryKey(string $tableName): ?Constraint
    {
        $tablePrimaryKey = $this->loadTableConstraints($tableName, self::PRIMARY_KEY);

        return $tablePrimaryKey instanceof Constraint ? $tablePrimaryKey : null;
    }

    /**
     * Loads all foreign keys for the given table.
     *
     * @param string $tableName table name.
     *
     * @throws Exception|InvalidConfigException|Throwable
     *
     * @return array foreign keys for the given table.
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
     * @param string $tableName table name.
     *
     * @throws Exception|InvalidConfigException|Throwable
     *
     * @return IndexConstraint[] indexes for the given table.
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

        /** @var array[] @indexes */
        $indexes = $this->normalizeRowKeyCase($indexes, true);
        $indexes = ArrayHelper::index($indexes, null, 'name');
        $result = [];

        /**
         * @var object|string|null $name
         * @var array<
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
     * @param string $tableName table name.
     *
     * @throws Exception|InvalidConfigException|Throwable
     *
     * @return array unique constraints for the given table.
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
     * @param string $tableName table name.
     *
     * @throws Exception|InvalidConfigException|Throwable
     *
     * @return array check constraints for the given table.
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
     * @param string $tableName table name.
     *
     * @throws NotSupportedException
     *
     * @return DefaultValueConstraint[] default value constraints for the given table.
     */
    protected function loadTableDefaultValues(string $tableName): array
    {
        throw new NotSupportedException('PostgreSQL does not support default value constraints.');
    }

    /**
     * Resolves the table name and schema name (if any).
     *
     * @param TableSchemaInterface $table the table metadata object.
     * @param string $name the table name
     */
    protected function resolveTableNames(TableSchemaInterface $table, string $name): void
    {
        $parts = explode('.', str_replace('"', '', $name));

        if (isset($parts[1])) {
            $table->schemaName($parts[0]);
            $table->name($parts[1]);
        } else {
            $table->schemaName($this->defaultSchema);
            $table->name($parts[0]);
        }

        if ($table->getSchemaName() !== $this->defaultSchema) {
            $name = (string) $table->getSchemaName() . '.' . $table->getName();
        } else {
            $name = $table->getName();
        }

        $table->fullName($name);
    }

    /**
     * @throws Exception|InvalidConfigException|Throwable
     */
    public function findViewNames(string $schema = ''): array
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

        $viewNames = $this->db->createCommand($sql, [':schemaName' => $schema])->queryColumn();
        if (!$viewNames) {
            return [];
        }

        return $viewNames;
    }

    /**
     * Collects the foreign key column details for the given table.
     *
     * @param TableSchemaInterface $table the table metadata
     *
     * @throws Exception|InvalidConfigException|Throwable
     */
    protected function findConstraints(TableSchemaInterface $table): void
    {
        $tableName = $table->getName();
        $tableSchema = $table->getSchemaName();

        /** @var mixed */
        $tableName = $this->db->getQuoter()->quoteValue($tableName);

        if ($tableSchema !== null) {
            /** @var mixed */
            $tableSchema = $this->db->getQuoter()->quoteValue($tableSchema);
        }

        /**
         * We need to extract the constraints de hard way since:
         * {@see http://www.postgresql.org/message-id/26677.1086673982@sss.pgh.pa.us}
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
            and c.relname=$tableName
            and ns.nspname=$tableSchema
        ORDER BY
            fns.nspname, fc.relname, a.attnum
        SQL;

        /** @var array{array{tableName: string, columns: array}} $constraints */
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
        $rows = $this->db->createCommand($sql)->queryAll();

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
         * @var int|string $foreingKeyName.
         * @var array{tableName: string, columns: array} $constraint
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
     * @param TableSchemaInterface $table the table metadata.
     *
     * @throws Exception|InvalidConfigException|Throwable
     *
     * @return array with index and column names.
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
     * @param TableSchemaInterface $table the table metadata
     *
     * @throws Exception|InvalidConfigException|Throwable
     *
     * @return array all unique indexes for the given table.
     */
    public function findUniqueIndexes(TableSchemaInterface $table): array
    {
        $uniqueIndexes = [];

        /** @var array{indexname: string, columnname: string} $row */
        foreach ($this->getUniqueIndexInformation($table) as $row) {
            /** @var array{indexname: string, columnname: string} $row */
            $row = $this->normalizeRowKeyCase($row, false);

            $column = $row['columnname'];

            if (!empty($column) && $column[0] === '"') {
                /**
                 * postgres will quote names that are not lowercase-only.
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
     * @param TableSchemaInterface $table the table metadata.
     *
     * @throws Exception|InvalidConfigException|JsonException|Throwable
     *
     * @return bool whether the table exists in the database.
     */
    protected function findColumns(TableSchemaInterface $table): bool
    {
        $tableName = $table->getName();
        $schemaName = $table->getSchemaName();
        $orIdentity = '';

        /** @var mixed */
        $tableName = $this->db->getQuoter()->quoteValue($tableName);

        if ($schemaName !== null) {
            /** @var mixed */
            $schemaName = $this->db->getQuoter()->quoteValue($schemaName);
        }

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
                AND c.relname = $tableName
                AND d.nspname = $schemaName
            ORDER BY
                a.attnum;
        SQL;

        $columns = $this->db->createCommand($sql)->queryAll();

        if (empty($columns)) {
            return false;
        }

        /** @var array $column */
        foreach ($columns as $column) {
            $column = $this->normalizeRowKeyCase($column, false);

            /** @psalm-var ColumnArray $column */
            $loadColumnSchema = $this->loadColumnSchema($column);
            $table->columns($loadColumnSchema->getName(), $loadColumnSchema);

            /** @var mixed */
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
     * Loads the column information into a {@see ColumnSchema} object.
     *
     * @psalm-param array{
     *   table_schema: string,
     *   table_name: string,
     *   column_name: string,
     *   data_type: string,
     *   type_type: string|null,
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
     * } $info column information.
     *
     * @return ColumnSchema the column schema object.
     */
    protected function loadColumnSchema(array $info): ColumnSchema
    {
        $column = $this->createColumnSchema();
        $column->allowNull($info['is_nullable']);
        $column->autoIncrement($info['is_autoinc']);
        $column->comment($info['column_comment']);
        $column->dbType($info['data_type']);
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
         * pg_get_serial_sequence() doesn't track DEFAULT value change. GENERATED BY IDENTITY columns always have null
         * default value.
         *
         * @var mixed $defaultValue
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
     * @param string $tableName table name.
     * @param string $returnType return type:
     * - primaryKey
     * - foreignKeys
     * - uniques
     * - checks
     *
     * @throws Exception|InvalidConfigException|Throwable
     *
     * @return array|Constraint|null (CheckConstraint|Constraint|ForeignKeyConstraint)[]|Constraint|null constraints.
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

        /** @var array<array-key, string> $actionTypes */
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

        /** @var array<array-key, array> $constraints */
        $constraints = $this->normalizeRowKeyCase($constraints, true);
        $constraints = ArrayHelper::index($constraints, null, ['type', 'name']);

        $result = [
            self::PRIMARY_KEY => null,
            self::FOREIGN_KEYS => [],
            self::UNIQUES => [],
            self::CHECKS => [],
        ];

        /**
         * @var string $type
         * @var array $names
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
     *
     * @return ColumnSchema column schema instance.
     */
    private function createColumnSchema(): ColumnSchema
    {
        return new ColumnSchema();
    }

    /**
     * Create a column schema builder instance giving the type and value precision.
     *
     * This method may be overridden by child classes to create a DBMS-specific column schema builder.
     *
     * @param string $type type of the column. See {@see ColumnSchemaBuilder::$type}.
     * @param array|int|string|null $length length or precision of the column. See {@see ColumnSchemaBuilder::$length}.
     *
     * @return ColumnSchemaBuilder column schema builder instance
     *
     * @psalm-param int|string|string[]|null $length
     */
    public function createColumnSchemaBuilder(string $type, int|string|array|null $length = null): ColumnSchemaBuilder
    {
        return new ColumnSchemaBuilder($type, $length);
    }

    /**
     * Returns the actual name of a given table name.
     *
     * This method will strip off curly brackets from the given table name and replace the percentage character '%' with
     * {@see ConnectionInterface::tablePrefix}.
     *
     * @param string $name the table name to be converted.
     *
     * @return string the real name of the given table name.
     */
    public function getRawTableName(string $name): string
    {
        if (str_contains($name, '{{')) {
            $name = preg_replace('/{{(.*?)}}/', '\1', $name);

            return str_replace('%', $this->db->getTablePrefix(), $name);
        }

        return $name;
    }

    /**
     * Returns the cache key for the specified table name.
     *
     * @param string $name the table name.
     *
     * @return array the cache key.
     */
    protected function getCacheKey(string $name): array
    {
        return array_merge([__CLASS__], $this->db->getCacheKey(), [$this->getRawTableName($name)]);
    }

    /**
     * Returns the cache tag name.
     *
     * This allows {@see refresh()} to invalidate all cached table schemas.
     *
     * @return string the cache tag name.
     */
    protected function getCacheTag(): string
    {
        return md5(serialize(array_merge([__CLASS__], $this->db->getCacheKey())));
    }

    /**
     * @return bool whether this DBMS supports [savepoint](http://en.wikipedia.org/wiki/Savepoint).
     */
    public function supportsSavepoint(): bool
    {
        return $this->db->isSavepointEnabled();
    }

    /**
     * Changes row's array key case to lower.
     *
     * @param array $row row's array or an array of row's arrays.
     * @param bool $multiple whether multiple rows or a single row passed.
     *
     * @return array normalized row or rows.
     */
    protected function normalizeRowKeyCase(array $row, bool $multiple): array
    {
        if ($multiple) {
            return array_map(static function (array $row) {
                return array_change_key_case($row, CASE_LOWER);
            }, $row);
        }

        return array_change_key_case($row, CASE_LOWER);
    }

    /**
     * @inheritDoc
     */
    public function getLastInsertID(?string $sequenceName = null): string
    {
        return $this->db->getLastInsertID($sequenceName);
    }

    /**
     * @throws Exception|InvalidConfigException|Throwable
     */
    public function getViewNames(string $schema = '', bool $refresh = false): array
    {
        if (!isset($this->viewNames[$schema]) || $refresh) {
            $this->viewNames[$schema] = $this->findViewNames($schema);
        }

        return is_array($this->viewNames[$schema]) ? $this->viewNames[$schema] : [];
    }
}
