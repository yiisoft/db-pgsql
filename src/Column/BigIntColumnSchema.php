<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Column;

use Yiisoft\Db\Schema\Column\BigIntColumnSchema as BaseBigIntColumnSchema;

final class BigIntColumnSchema extends BaseBigIntColumnSchema implements IntegerColumnSchemaInterface
{
    /**
     * @var string|null Name of an associated sequence if column is auto incremental.
     */
    private string|null $sequenceName = null;

    public function getSequenceName(): string|null
    {
        return $this->sequenceName;
    }

    public function sequenceName(string|null $sequenceName): void
    {
        $this->sequenceName = $sequenceName;
    }
}
