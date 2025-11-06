<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Column;

use Yiisoft\Db\Pgsql\Data\StructuredLazyArray;
use Yiisoft\Db\Schema\Column\AbstractStructuredColumn;

use function is_string;

final class StructuredColumn extends AbstractStructuredColumn
{
    /**
     * @param string|null $value
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    public function phpTypecast(mixed $value): ?array
    {
        if (is_string($value)) {
            return (new StructuredLazyArray($value, $this->columns))->getValue();
        }

        return $value;
    }
}
