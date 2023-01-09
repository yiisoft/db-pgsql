<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql;

use Throwable;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\QueryBuilder\AbstractDDLQueryBuilder;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;
use Yiisoft\Db\Schema\AbstractColumnSchemaBuilder;
use Yiisoft\Db\Schema\QuoterInterface;
use Yiisoft\Db\Schema\SchemaInterface;

use function array_diff;
use function array_unshift;
use function explode;
use function implode;
use function preg_match;
use function preg_replace;
use function str_contains;

final class DDLQueryBuilder extends AbstractDDLQueryBuilder
{
    public function __construct(
        private QueryBuilderInterface $queryBuilder,
        private QuoterInterface $quoter,
        private SchemaInterface $schema
    ) {
        parent::__construct($queryBuilder, $quoter, $schema);
    }

    /**
     * @throws NotSupportedException
     */
    public function addDefaultValue(string $name, string $table, string $column, mixed $value): string
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported by PostgreSQL.');
    }

    public function alterColumn(string $table, string $column, AbstractColumnSchemaBuilder|string $type): string
    {
        $columnName = $this->quoter->quoteColumnName($column);
        $tableName = $this->quoter->quoteTableName($table);

        /**
         * {@see https://github.com/yiisoft/yii2/issues/4492}
         * {@see http://www.postgresql.org/docs/9.1/static/sql-altertable.html}
         */
        if (preg_match('/^(DROP|SET|RESET|USING)\s+/i', (string) $type)) {
            return "ALTER TABLE $tableName ALTER COLUMN $columnName $type";
        }

        $type = 'TYPE ' . $this->queryBuilder->getColumnType($type);
        $multiAlterStatement = [];
        $constraintPrefix = preg_replace('/[^a-z0-9_]/i', '', $table . '_' . $column);

        if (preg_match('/\s+DEFAULT\s+(["\']?\w*["\']?)/i', $type, $matches)) {
            $type = preg_replace('/\s+DEFAULT\s+(["\']?\w*["\']?)/i', '', $type);
            $multiAlterStatement[] = "ALTER COLUMN $columnName SET DEFAULT $matches[1]";
        }

        $type = preg_replace('/\s+NOT\s+NULL/i', '', $type, -1, $count);

        if ($count) {
            $multiAlterStatement[] = "ALTER COLUMN $columnName SET NOT NULL";
        } else {
            /** remove additional null if any */
            $type = preg_replace('/\s+NULL/i', '', $type, -1, $count);
            if ($count) {
                $multiAlterStatement[] = "ALTER COLUMN $columnName DROP NOT NULL";
            }
        }

        if (preg_match('/\s+CHECK\s+\((.+)\)/i', $type, $matches)) {
            $type = preg_replace('/\s+CHECK\s+\((.+)\)/i', '', $type);
            $multiAlterStatement[] = "ADD CONSTRAINT {$constraintPrefix}_check CHECK ($matches[1])";
        }

        $type = preg_replace('/\s+UNIQUE/i', '', $type, -1, $count);

        if ($count) {
            $multiAlterStatement[] = "ADD UNIQUE ($columnName)";
        }

        /** add what's left at the beginning */
        array_unshift($multiAlterStatement, "ALTER COLUMN $columnName $type");

        return 'ALTER TABLE ' . $tableName . ' ' . implode(', ', $multiAlterStatement);
    }

    /**
     * @throws Exception
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function checkIntegrity(string $schema = '', string $table = '', bool $check = true): string
    {
        /** @psalm-var Schema $schemaInstance */
        $schemaInstance = $this->schema;
        $enable = $check ? 'ENABLE' : 'DISABLE';
        $schema = $schema ?: $schemaInstance->getDefaultSchema();
        $tableNames = [];
        $viewNames = [];

        if ($schema !== null) {
            $tableNames = $table ? [$table] : $schemaInstance->getTableNames($schema);
            $viewNames = $schemaInstance->getViewNames($schema);
        }

        $tableNames = array_diff($tableNames, $viewNames);
        $command = '';

        /** @psalm-var string[] $tableNames */
        foreach ($tableNames as $tableName) {
            $tableName = $this->quoter->quoteTableName("$schema.$tableName");
            $command .= "ALTER TABLE $tableName $enable TRIGGER ALL; ";
        }

        /** enable to have ability to alter several tables */
        //$pdo = $db->getSlavePdo();
        //$pdo?->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

        return $command;
    }

    /**
     * @throws Exception|InvalidArgumentException
     */
    public function createIndex(string $name, string $table, array|string $columns, ?string $indexType = null, ?string $indexMethod = null): string
    {
        return 'CREATE ' . ($indexType ? ($indexType . ' ') : '') . 'INDEX '
            . $this->quoter->quoteTableName($name) . ' ON '
            . $this->quoter->quoteTableName($table)
            . ($indexMethod !== null ? " USING $indexMethod" : '')
            . ' (' . $this->queryBuilder->buildColumns($columns) . ')';
    }

    /**
     * @throws NotSupportedException
     */
    public function dropDefaultValue(string $name, string $table): string
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported by PostgreSQL.');
    }

    public function dropIndex(string $name, string $table): string
    {
        if (str_contains($table, '.') && !str_contains($name, '.')) {
            if (str_contains($table, '{{')) {
                $table = preg_replace('/{{(.*?)}}/', '\1', $table);
                [$schema] = explode('.', $table);
                if (!str_contains($schema, '%')) {
                    $name = $schema . '.' . $name;
                } else {
                    $name = '{{' . $schema . '.' . $name . '}}';
                }
            } else {
                [$schema] = explode('.', $table);
                $name = $schema . '.' . $name;
            }
        }

        return 'DROP INDEX ' . $this->quoter->quoteTableName($name);
    }

    public function truncateTable(string $table): string
    {
        return 'TRUNCATE TABLE ' . $this->quoter->quoteTableName($table) . ' RESTART IDENTITY';
    }

    public function renameTable(string $oldName, string $newName): string
    {
        return 'ALTER TABLE '
            . $this->quoter->quoteTableName($oldName)
            . ' RENAME TO '
            . $this->quoter->quoteTableName($newName);
    }
}
