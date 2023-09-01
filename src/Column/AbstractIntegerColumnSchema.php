<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Column;

use Yiisoft\Db\Schema\Column\AbstractColumnSchema;
use Yiisoft\Db\Schema\SchemaInterface;

abstract class AbstractIntegerColumnSchema extends AbstractColumnSchema implements IntegerColumnSchemaInterface
{
    /**
     * @var string|null Name of an associated sequence if column is auto incremental.
     */
    private string|null $sequenceName = null;

    public function __construct(string $name)
    {
        parent::__construct($name);

        $this->type(SchemaInterface::TYPE_INTEGER);
        $this->phpType(SchemaInterface::PHP_TYPE_INTEGER);
    }

    /**
     * @return string|null name of an associated sequence if column is auto incremental.
     */
    public function getSequenceName(): string|null
    {
        return $this->sequenceName;
    }

    /**
     * Set the name of an associated sequence if a column is auto incremental.
     */
    public function sequenceName(string|null $sequenceName): void
    {
        $this->sequenceName = $sequenceName;
    }
}
