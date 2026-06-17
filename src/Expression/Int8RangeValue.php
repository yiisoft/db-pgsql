<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Expression;

use RuntimeException;
use Yiisoft\Db\Expression\ExpressionInterface;

use const PHP_INT_MAX;
use const PHP_INT_MIN;

final class Int8RangeValue implements ExpressionInterface
{
    public function __construct(
        public readonly int|string|null $lower = null,
        public readonly int|string|null $upper = null,
        public readonly bool $includeLower = true,
        public readonly bool $includeUpper = true,
    ) {}

    /**
     * Returns the lower and upper bounds of a range, inclusive.
     *
     * @psalm-return array{0: int|string|null, 1: int|string|null}
     */
    public function getBounds(): array
    {
        $lower = $this->lower === null || $this->includeLower
            ? $this->lower
            : (
                PHP_INT_MIN <= $this->lower && $this->lower < PHP_INT_MAX
                ? (int) $this->lower + 1
                : throw new RuntimeException(
                    'Lower bound cannot be determined from the excluded value of a bigint range.',
                )
            );

        $upper = $this->upper === null || $this->includeUpper
            ? $this->upper
            : (
                PHP_INT_MIN < $this->upper && $this->upper <= PHP_INT_MAX
                ? (int) $this->upper - 1
                : throw new RuntimeException(
                    'Upper bound cannot be determined from the excluded value of a bigint range.',
                )
            );

        return [$lower, $upper];
    }
}
