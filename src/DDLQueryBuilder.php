<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql;

use Throwable;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Pgsql\PDO\SchemaPDOPgsql;
use Yiisoft\Db\Query\DDLQueryBuilder as AbstractDDLQueryBuilder;
use Yiisoft\Db\Query\QueryBuilderInterface;

final class DDLQueryBuilder extends AbstractDDLQueryBuilder
{
    public function __construct(private QueryBuilderInterface $queryBuilder)
    {
        parent::__construct($queryBuilder);
    }

    public function alterColumn(string $table, string $column, $type): string
    {
        $columnName = $this->queryBuilder->quoter()->quoteColumnName($column);
        $tableName = $this->queryBuilder->quoter()->quoteTableName($table);

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
     * @throws Exception|InvalidConfigException|NotSupportedException|Throwable
     */
    public function checkIntegrity(string $schema = '', string $table = '', bool $check = true): string
    {
        /** @var SchemaPDOPgsql */
        $schemaInstance = $this->queryBuilder->schema();
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

        foreach ($tableNames as $tableName) {
            $tableName = $this->queryBuilder->quoter()->quoteTableName("$schema.$tableName");
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
    public function createIndex(string $name, string $table, array|string $columns, $unique = false): string
    {
        if ($unique === $this->queryBuilder::INDEX_UNIQUE || $unique === true) {
            $index = false;
            $unique = true;
        } else {
            $index = $unique;
            $unique = false;
        }

        return ($unique ? 'CREATE UNIQUE INDEX ' : 'CREATE INDEX ')
            . $this->queryBuilder->quoter()->quoteTableName($name) . ' ON '
            . $this->queryBuilder->quoter()->quoteTableName($table)
            . ($index !== false ? " USING $index" : '')
            . ' (' . $this->queryBuilder->buildColumns($columns) . ')';
    }

    public function dropIndex(string $name, string $table): string
    {
        if (str_contains($table, '.') && !str_contains($name, '.')) {
            if (str_contains($table, '{{')) {
                $table = preg_replace('/{{(.*?)}}/', '\1', $table);
                [$schema, $table] = explode('.', $table);
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

        return 'DROP INDEX ' . $this->queryBuilder->quoter()->quoteTableName($name);
    }

    public function renameTable(string $oldName, string $newName): string
    {
        return 'ALTER TABLE '
            . $this->queryBuilder->quoter()->quoteTableName($oldName)
            . ' RENAME TO '
            . $this->queryBuilder->quoter()->quoteTableName($newName);
    }

    public function truncateTable(string $table): string
    {
        return 'TRUNCATE TABLE ' . $this->queryBuilder->quoter()->quoteTableName($table) . ' RESTART IDENTITY';
    }
}
