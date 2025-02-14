<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql;

use Throwable;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\QueryBuilder\AbstractDDLQueryBuilder;
use Yiisoft\Db\Schema\Column\ColumnInterface;

use function array_diff;
use function explode;
use function implode;
use function is_string;
use function preg_match;
use function preg_replace;
use function str_contains;

/**
 * Implements a (Data Definition Language) SQL statements for PostgreSQL Server.
 */
final class DDLQueryBuilder extends AbstractDDLQueryBuilder
{
    public function addDefaultValue(string $table, string $name, string $column, mixed $value): string
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported by PostgreSQL.');
    }

    public function alterColumn(string $table, string $column, ColumnInterface|string $type): string
    {
        $columnName = $this->quoter->quoteColumnName($column);
        $tableName = $this->quoter->quoteTableName($table);

        /**
         * @link https://github.com/yiisoft/yii2/issues/4492
         * @link https://www.postgresql.org/docs/9.1/static/sql-altertable.html
         */
        if (is_string($type)) {
            if (preg_match('/^(DROP|SET|RESET|USING)\s+/i', $type) === 1) {
                return "ALTER TABLE $tableName ALTER COLUMN $columnName $type";
            }

            $type = $this->schema->getColumnFactory()->fromDefinition($type);
        }

        $columnDefinitionBuilder = $this->queryBuilder->getColumnDefinitionBuilder();

        $multiAlterStatement = ["ALTER COLUMN $columnName TYPE " . $columnDefinitionBuilder->buildAlter($type)];

        if ($type->hasDefaultValue()) {
            $defaultValue = $type->dbTypecast($type->getDefaultValue());
            $defaultValue = $this->queryBuilder->prepareValue($defaultValue);

            $multiAlterStatement[] = "ALTER COLUMN $columnName SET DEFAULT $defaultValue";
        }

        match ($type->isNotNull()) {
            true => $multiAlterStatement[] = "ALTER COLUMN $columnName SET NOT NULL",
            false => $multiAlterStatement[] = "ALTER COLUMN $columnName DROP NOT NULL",
            default => null,
        };

        $check = $type->getCheck();
        if (!empty($check)) {
            $constraintPrefix = preg_replace('/\W/', '', $table . '_' . $column);
            $multiAlterStatement[] = "ADD CONSTRAINT {$constraintPrefix}_check CHECK ($check)";
        }

        if ($type->isUnique()) {
            $multiAlterStatement[] = "ADD UNIQUE ($columnName)";
        }

        return "ALTER TABLE $tableName " . implode(', ', $multiAlterStatement);
    }

    /**
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

        return $command;
    }

    public function createIndex(string $table, string $name, array|string $columns, ?string $indexType = null, ?string $indexMethod = null): string
    {
        return 'CREATE ' . (!empty($indexType) ? $indexType . ' ' : '') . 'INDEX '
            . $this->quoter->quoteTableName($name) . ' ON '
            . $this->quoter->quoteTableName($table)
            . (!empty($indexMethod) ? " USING $indexMethod" : '')
            . ' (' . $this->queryBuilder->buildColumns($columns) . ')';
    }

    public function dropDefaultValue(string $table, string $name): string
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported by PostgreSQL.');
    }

    public function dropIndex(string $table, string $name): string
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
