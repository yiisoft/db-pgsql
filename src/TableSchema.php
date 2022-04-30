<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql;

use Yiisoft\Db\Schema\TableSchema as AbstractTableSchema;

/**
 * The class TableSchema represents the metadata of a database table.
 *
 * @property array $columnNames List of column names. This property is read-only.
 */
final class TableSchema extends AbstractTableSchema
{
}
