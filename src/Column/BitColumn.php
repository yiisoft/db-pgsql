<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Column;

use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Schema\Column\BitColumn as BaseBitColumn;

use function bindec;
use function decbin;
use function gettype;
use function str_pad;

final class BitColumn extends BaseBitColumn
{
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
