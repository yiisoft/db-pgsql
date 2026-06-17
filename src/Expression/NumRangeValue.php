<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Expression;

use RuntimeException;
use Yiisoft\Db\Expression\ExpressionInterface;

final class NumRangeValue implements ExpressionInterface
{
    public function __construct(
        public readonly int|float|null $lower = null,
        public readonly int|float|null $upper = null,
        public readonly bool $includeLower = true,
        public readonly bool $includeUpper = true,
    ) {}

    /**
     * Returns the lower and upper bounds of a range, inclusive.
     *
     * @psalm-return array{0: int|float|null, 1: int|float|null}
     */
    public function getBounds(): array
    {
        $lower = $this->lower === null || $this->includeLower
            ? $this->lower
            : throw new RuntimeException(
                'Lower bound cannot be determined from the excluded value of a numeric range.',
            );

        $upper = $this->upper === null || $this->includeUpper
            ? $this->upper
            : throw new RuntimeException(
                'Upper bound cannot be determined from the excluded value of a numeric range.',
            );

        return [$lower, $upper];
    }
}
