<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Column;

use Yiisoft\Db\Pgsql\Data\LazyArrayStructured;
use Yiisoft\Db\Schema\Column\AbstractStructuredColumn;

use function is_string;

final class StructuredLazyColumn extends AbstractStructuredColumn
{
    /**
     * @param string|null $value
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    public function phpTypecast(mixed $value): LazyArrayStructured|null
    {
        if (is_string($value)) {
            return new LazyArrayStructured($value, $this->columns);
        }

        return $value;
    }
}
