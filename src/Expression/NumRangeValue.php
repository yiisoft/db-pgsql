<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Expression;

use Yiisoft\Db\Expression\ExpressionInterface;
use RuntimeException;

final class NumRangeValue implements ExpressionInterface
{
    public function __construct(
        public readonly int|float|null $lower = null,
        public readonly int|float|null $upper = null,
        public readonly bool $includeLower = true,
        public readonly bool $includeUpper = true,
    ) {}

    public function getBounds(): array
    {
        $lower = $this->lower === null || $this->includeLower
            ? $this->lower
            : throw new RuntimeException(
                'Lower bound cannot be determined from the excluded values of a numeric range.',
            );

        $upper = $this->upper === null || $this->includeUpper
            ? $this->upper
            : throw new RuntimeException(
                'Upper bound cannot be determined from the excluded values of a numeric range.',
            );

        return [$lower, $upper];
    }
}
