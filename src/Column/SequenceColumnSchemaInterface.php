<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Column;

use Yiisoft\Db\Schema\Column\ColumnSchemaInterface;

interface SequenceColumnSchemaInterface extends ColumnSchemaInterface
{
    /**
     * Returns name of an associated sequence if column is auto incremental.
     *
     * @psalm-mutation-free
     */
    public function getSequenceName(): string|null;

    /**
     * Set the name of an associated sequence if a column is auto incremental.
     */
    public function sequenceName(string|null $sequenceName): static;
}
