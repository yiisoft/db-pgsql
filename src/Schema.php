<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Constant\ReferentialAction;
use Yiisoft\Db\Constraint\Check;
use Yiisoft\Db\Constraint\ForeignKey;
use Yiisoft\Db\Constraint\Index;
use Yiisoft\Db\Driver\Pdo\AbstractPdoSchema;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Helper\DbArrayHelper;
use Yiisoft\Db\Pgsql\Column\SequenceColumnInterface;
use Yiisoft\Db\Schema\Column\ColumnInterface;
use Yiisoft\Db\Schema\SchemaInterface;
use Yiisoft\Db\Schema\TableSchema;
use Yiisoft\Db\Schema\TableSchemaInterface;

use function array_change_key_case;
use function array_column;
use function array_map;
use function array_unique;
use function array_values;
use function explode;
use function is_string;
use function preg_match;
use function str_replace;
use function substr;

/**
 * Implements the PostgreSQL Server specific schema, supporting PostgreSQL Server version 9.6 and above.
 *
 * @psalm-type ColumnArray = array{
 *   column_name: string,
 *   data_type: string,
 *   type_type: string|null,
 *   type_scheme: string,
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
 *     foreign_table_schema: string,
 *     foreign_table_name: string,
 *     foreign_column_name: string,
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
    protected function findConstraints(TableSchemaInterface $table): void
    {
        $tableName = $this->resolveFullName($table->getName(), $table->getSchemaName());

        $table->checks(...$this->getTableMetadata($tableName, SchemaInterface::CHECKS));
        $table->foreignKeys(...$this->getTableMetadata($tableName, SchemaInterface::FOREIGN_KEYS));
        $table->indexes(...$this->getTableMetadata($tableName, SchemaInterface::INDEXES));
    }

    /**
     * @var string The default schema used for the current session.
     */
    protected string $defaultSchema = 'public';

    protected function findSchemaNames(): array
    {
        $sql = <<<SQL
        SELECT "ns"."nspname"
        FROM "pg_namespace" AS "ns"
        WHERE "ns"."nspname" != 'information_schema' AND "ns"."nspname" NOT LIKE 'pg_%'
        ORDER BY "ns"."nspname" ASC
        SQL;

        /** @var string[] */
        return $this->db->createCommand($sql)->queryColumn();
    }

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
            ':schemaName' => $tableSchema->getSchemaName() ?: $this->defaultSchema,
            ':tableName' => $tableSchema->getName(),
        ])->queryScalar();

        $tableSchema->comment(is_string($comment) ? $comment : null);
    }

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

        /** @var string[] */
        return $this->db->createCommand($sql, [':schemaName' => $schema])->queryColumn();
    }

    protected function loadTableSchema(string $name): TableSchemaInterface|null
    {
        $table = new TableSchema(...$this->db->getQuoter()->getTableNameParts($name));

        if ($this->findColumns($table)) {
            $this->findTableComment($table);
            $this->findConstraints($table);
            return $table;
        }

        return null;
    }

    protected function loadTableForeignKeys(string $tableName): array
    {
        /** @var ForeignKey[] */
        return $this->loadTableConstraints($tableName, self::FOREIGN_KEYS);
    }

    protected function loadTableIndexes(string $tableName): array
    {
        $sql = <<<SQL
        SELECT
            "ic"."relname" AS "name",
            "ia"."attname" AS "column_name",
            "i"."indisunique" AS "is_unique",
            "i"."indisprimary" AS "is_primary_key"
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

        $nameParts = $this->db->getQuoter()->getTableNameParts($tableName);
        $indexes = $this->db->createCommand($sql, [
            ':schemaName' => $nameParts['schemaName'] ?? $this->defaultSchema,
            ':tableName' => $nameParts['name'],
        ])->queryAll();

        $indexes = array_map(array_change_key_case(...), $indexes);
        $indexes = DbArrayHelper::arrange($indexes, ['name']);
        $result = [];

        /**
         * @var string $name
         * @psalm-var list<
         *   array{
         *     name: string,
         *     column_name: string,
         *     is_unique: bool,
         *     is_primary_key: bool
         *   }
         * > $index
         */
        foreach ($indexes as $name => $index) {
            $result[$name] = new Index(
                $name,
                array_column($index, 'column_name'),
                $index[0]['is_unique'],
                $index[0]['is_primary_key'],
            );
        }

        return $result;
    }

    protected function loadTableChecks(string $tableName): array
    {
        /** @var Check[] */
        return $this->loadTableConstraints($tableName, self::CHECKS);
    }

    protected function loadTableDefaultValues(string $tableName): array
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported by PostgreSQL.');
    }

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

        /** @var string[] */
        return $this->db->createCommand($sql, [':schemaName' => $schema])->queryColumn();
    }

    /**
     * Collects the metadata of table columns.
     *
     * @param TableSchemaInterface $table The table metadata.
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

        $schemaName = $table->getSchemaName() ?: $this->defaultSchema;
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

            if ($column instanceof SequenceColumnInterface
                && $column->isPrimaryKey()
                && $table->getSequenceName() === null
            ) {
                $table->sequenceName($column->getSequenceName());
            }
        }

        return true;
    }

    protected function resolveFullName(string $name, string $schemaName = ''): string
    {
        $quoter = $this->db->getQuoter();
        $rawName = $quoter->getRawTableName($name);

        return match ($schemaName) {
            '', 'pg_catalog', $this->defaultSchema => $rawName,
            default => $quoter->getRawTableName($schemaName) . ".$rawName",
        };
    }

    /**
     * @psalm-param array{
     *     "pgsql:oid": int,
     *     "pgsql:table_oid": int,
     *     table?: string,
     *     native_type: string,
     *     pdo_type: int,
     *     name: string,
     *     len: int,
     *     precision: int,
     * } $metadata
     *
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    protected function loadResultColumn(array $metadata): ColumnInterface|null
    {
        if (empty($metadata['native_type'])) {
            return null;
        }

        $dbType = $metadata['native_type'];

        $columnInfo = ['fromResult' => true];

        if (!empty($metadata['table'])) {
            $columnInfo['table'] = $metadata['table'];
            $columnInfo['name'] = $metadata['name'];
        } elseif (!empty($metadata['name'])) {
            $columnInfo['name'] = $metadata['name'];
        }

        if ($metadata['precision'] !== -1) {
            $columnInfo['size'] = match ($dbType) {
                'varchar', 'bpchar' => $metadata['precision'] - 4,
                'numeric' => (($metadata['precision'] - 4) >> 16) & 0xFFFF,
                'interval' => ($metadata['precision'] & 0xFFFF) === 0xFFFF ? 6 : $metadata['precision'] & 0xFFFF,
                default => $metadata['precision'],
            };

            if ($dbType === 'numeric') {
                $columnInfo['scale'] = ($metadata['precision'] - 4) & 0xFFFF;
            }
        }

        $isArray = $dbType[0] === '_';

        if ($isArray) {
            $dbType = substr($dbType, 1);
        }

        if ($metadata['pgsql:oid'] > 16000) {
            /** @var string[] $typeInfo */
            $typeInfo = $this->db->createCommand(
                <<<SQL
                SELECT
                    ns.nspname AS schema,
                    COALESCE(t2.typname, t.typname) AS typname,
                    COALESCE(t2.typtype, t.typtype) AS typtype,
                    CASE WHEN COALESCE(t2.typtype, t.typtype) = 'e'::char
                        THEN array_to_string(
                            (
                                SELECT array_agg(enumlabel)
                                FROM pg_enum
                                WHERE enumtypid = COALESCE(t2.oid, t.oid)
                            )::varchar[],
                        ',')
                        ELSE NULL
                    END AS enum_values
                FROM pg_type AS t
                LEFT JOIN pg_type AS t2 ON t.typcategory='A' AND t2.oid = t.typelem OR t.typbasetype > 0 AND t2.oid = t.typbasetype
                LEFT JOIN pg_namespace AS ns ON ns.oid = COALESCE(t2.typnamespace, t.typnamespace)
                WHERE t.oid = :oid
                SQL,
                [':oid' => $metadata['pgsql:oid']]
            )->queryOne();

            $dbType = $this->resolveFullName($typeInfo['typname'], $typeInfo['schema']);

            if ($typeInfo['typtype'] === 'c') {
                $structured = new TableSchema($typeInfo['typname'], $typeInfo['schema']);

                if ($this->findColumns($structured)) {
                    $columnInfo['columns'] = $structured->getColumns();
                }

                $columnInfo['type'] = ColumnType::STRUCTURED;
            } elseif (!empty($typeInfo['enum_values'])) {
                $columnInfo['enumValues'] = explode(',', str_replace(["''"], ["'"], $typeInfo['enum_values']));
            }
        }

        $columnFactory = $this->db->getColumnFactory();
        $column = $columnFactory->fromDbType($dbType, $columnInfo);

        if ($isArray) {
            $columnInfo['dbType'] = $dbType;
            $columnInfo['column'] = $column;

            return $columnFactory->fromType(ColumnType::ARRAY, $columnInfo);
        }

        return $column;
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
        $columnFactory = $this->db->getColumnFactory();
        $dbType = $this->resolveFullName($info['data_type'], $info['type_scheme']);

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
            $structured = new TableSchema($info['data_type'], $info['type_scheme']);

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
            $columnInfo['sequenceName'] = $this->clarifyFullName($info['sequence_name']);
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
     * - foreignKeys
     * - checks
     *
     * @return Check[]|ForeignKey[] Constraints.
     */
    private function loadTableConstraints(string $tableName, string $returnType): array
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
        WHERE "tcns"."nspname" = :schemaName AND "tc"."relname" = :tableName AND "c"."contype" IN ('c', 'f')
        ORDER BY "a"."attnum" ASC, "fa"."attnum" ASC
        SQL;

        /** @psalm-var ReferentialAction::*[] $actionTypes */
        $actionTypes = [
            'a' => ReferentialAction::NO_ACTION,
            'r' => ReferentialAction::RESTRICT,
            'c' => ReferentialAction::CASCADE,
            'n' => ReferentialAction::SET_NULL,
            'd' => ReferentialAction::SET_DEFAULT,
        ];

        $nameParts = $this->db->getQuoter()->getTableNameParts($tableName);
        $constraints = $this->db->createCommand($sql, [
            ':schemaName' => $nameParts['schemaName'] ?? $this->defaultSchema,
            ':tableName' => $nameParts['name'],
        ])->queryAll();

        $constraints = array_map(array_change_key_case(...), $constraints);
        $constraints = DbArrayHelper::arrange($constraints, ['type', 'name']);

        $result = [
            self::FOREIGN_KEYS => [],
            self::CHECKS => [],
        ];

        /**
         * @var string $type
         * @psalm-var array<string, ConstraintArray> $names
         */
        foreach ($constraints as $type => $names) {
            foreach ($names as $name => $constraint) {
                match ($type) {
                    'f' => $result[self::FOREIGN_KEYS][$name] = new ForeignKey(
                        $name,
                        array_values(array_unique(array_column($constraint, 'column_name'))),
                        $constraint[0]['foreign_table_schema'],
                        $constraint[0]['foreign_table_name'],
                        array_values(array_unique(array_column($constraint, 'foreign_column_name'))),
                        $actionTypes[$constraint[0]['on_delete']] ?? null,
                        $actionTypes[$constraint[0]['on_update']] ?? null,
                    ),
                    'c' => $result[self::CHECKS][$name] = new Check(
                        $name,
                        array_column($constraint, 'column_name'),
                        $constraint[0]['check_expr'],
                    ),
                };
            }
        }

        foreach ($result as $type => $data) {
            $this->setTableMetadata($tableName, $type, $data);
        }

        return $result[$returnType];
    }
}
