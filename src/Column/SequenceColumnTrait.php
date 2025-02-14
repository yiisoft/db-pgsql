<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Column;

trait SequenceColumnTrait
{
    /**
     * @var string|null Name of an associated sequence if column is auto incremental.
     */
    protected string|null $sequenceName = null;

    /**
     * @psalm-mutation-free
     */
    public function getSequenceName(): string|null
    {
        return $this->sequenceName;
    }

    public function sequenceName(string|null $sequenceName): static
    {
        $this->sequenceName = $sequenceName;
        return $this;
    }
}
