<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Column;

use Yiisoft\Db\Schema\Column\ColumnSchemaInterface;

interface StructuredColumnSchemaInterface extends ColumnSchemaInterface
{
    /**
     * Set columns of the composite type.
     *
     * @param ColumnSchemaInterface[] $columns The metadata of the composite type columns.
     * @psalm-param array<string, ColumnSchemaInterface> $columns
     */
    public function columns(array $columns): static;

    /**
     * Get the metadata of the composite type columns.
     *
     * @return ColumnSchemaInterface[]
     */
    public function getColumns(): array;
}
