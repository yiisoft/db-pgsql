<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Column;

use const PHP_INT_SIZE;

/**
 * Provides column class names based on the PHP architecture (64-bit or 32-bit) and additional information if available.
 *
 * @internal
 */
final class ColumnClassName
{
    /**
     * @psalm-return class-string<BitColumn|BigBitColumn>
     */
    public static function bit(?int $size): string
    {
        return !empty($size) && ($size > 63 || PHP_INT_SIZE !== 8 && $size > 31)
            ? BigBitColumn::class
            : BitColumn::class;
    }
}
