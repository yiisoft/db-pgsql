<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Column;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Schema\Column\AbstractColumn;

use function bindec;

final class BitColumn extends AbstractColumn
{
    protected const DEFAULT_TYPE = ColumnType::BIT;

    public function dbTypecast(mixed $value): string|ExpressionInterface|null
    {
        return BitColumnInternal::dbTypecast($value, $this->getSize());
    }

    public function phpTypecast(mixed $value): ?int
    {
        /** @var int|string|null $value */
        if (is_string($value)) {
            /** @var int */
            return bindec($value);
        }

        return $value;
    }
}
