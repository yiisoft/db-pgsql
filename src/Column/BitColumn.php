<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Column;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Schema\Column\AbstractColumn;

use function bindec;
use function decbin;
use function gettype;
use function is_string;
use function str_pad;

final class BitColumn extends AbstractColumn
{
    protected const DEFAULT_TYPE = ColumnType::BIT;

    /** @psalm-suppress RedundantCast */
    public function dbTypecast(mixed $value): string|ExpressionInterface|null
    {
        return match (gettype($value)) {
            'integer', 'double' => str_pad(decbin((int) $value), (int) $this->getSize(), '0', STR_PAD_LEFT),
            'NULL' => null,
            'boolean' => $value ? '1' : '0',
            'string' => $value === '' ? null : $value,
            default => $value instanceof ExpressionInterface ? $value : (string) $value,
        };
    }

    public function phpTypecast(mixed $value): int|string|null
    {
        /** @var int|string|null $value */
        if ($this->isBig() || !is_string($value)) {
            return $value;
        }

        /** @var int */
        return bindec($value);
    }

    private function isBig(): bool
    {
        $size = $this->getSize();
        return $size === null
            || $size >= (PHP_INT_SIZE === 8 ? 64 : 32);
    }
}
