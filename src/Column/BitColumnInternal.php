<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Column;

use Yiisoft\Db\Expression\ExpressionInterface;

use function decbin;
use function gettype;
use function str_pad;

use const PHP_INT_SIZE;
use const STR_PAD_LEFT;

/**
 * Helper utilities for `bit` and `varbit` column handling.
 *
 * @internal
 */
final class BitColumnInternal
{
    final private function __construct() {}

    /** @psalm-return class-string<BitColumn|BigBitColumn> */
    public static function className(?int $size): string
    {
        return !empty($size) && ($size > 63 || PHP_INT_SIZE !== 8 && $size > 31)
            ? BigBitColumn::class
            : BitColumn::class;
    }

    /** @psalm-suppress RedundantCast */
    public static function dbTypecast(mixed $value, ?int $size): string|ExpressionInterface|null
    {
        return match (gettype($value)) {
            'integer', 'double' => self::addZero(decbin((int) $value), $size),
            'NULL' => null,
            'boolean' => self::addZero($value ? '1' : '0', $size),
            'string' => $value === '' ? null : $value,
            default => $value instanceof ExpressionInterface ? $value : (string) $value,
        };
    }

    private static function addZero(string $value, ?int $size): string
    {
        return str_pad($value, (int) $size, '0', STR_PAD_LEFT);
    }
}
