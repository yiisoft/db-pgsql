<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Expression;

use InvalidArgumentException;

use const PHP_INT_MAX;
use const PHP_INT_MIN;

/**
 * @implements RangeValueInterface<int|string>
 */
final class Int8RangeValue implements RangeValueInterface
{
    public function __construct(
        public readonly int|string|null $lower = null,
        public readonly int|string|null $upper = null,
        public readonly bool $includeLower = true,
        public readonly bool $includeUpper = true,
    ) {}

    public function getBounds(): array
    {
        $lower = $this->lower === null || $this->includeLower
            ? $this->lower
            : (
                PHP_INT_MIN <= $this->lower && $this->lower < PHP_INT_MAX
                ? (int) $this->lower + 1
                : throw new InvalidArgumentException('Lower bound cannot be determined from the excluded value of a bigint range.')
            );

        $upper = $this->upper === null || $this->includeUpper
            ? $this->upper
            : (
                PHP_INT_MIN < $this->upper && $this->upper <= PHP_INT_MAX
                ? (int) $this->upper - 1
                : throw new InvalidArgumentException('Upper bound cannot be determined from the excluded value of a bigint range.')
            );

        return [$lower, $upper];
    }
}
