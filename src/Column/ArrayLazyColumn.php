<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Column;

use Yiisoft\Db\Pgsql\Data\LazyArray;
use Yiisoft\Db\Schema\Column\AbstractArrayColumn;

use function is_string;

final class ArrayLazyColumn extends AbstractArrayColumn
{
    /**
     * @param string|null $value
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    public function phpTypecast(mixed $value): ?LazyArray
    {
        if (is_string($value)) {
            return new LazyArray($value, $this->getColumn(), $this->dimension);
        }

        return $value;
    }
}
