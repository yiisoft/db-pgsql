<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Expression;

use Yiisoft\Db\Expression\ExpressionInterface;

final class Int4RangeValue implements ExpressionInterface
{
    public function __construct(
        public readonly ?int $lower = null,
        public readonly ?int $upper = null,
        public readonly bool $includeLower = true,
        public readonly bool $includeUpper = true,
    ) {}

    /**
     * Returns the lower and upper bounds of a range, inclusive.
     *
     * @psalm-return array{0: ?int, 1: ?int}
     */
    public function getBounds(): array
    {
        $lower = $this->lower === null || $this->includeLower
            ? $this->lower
            : $this->lower + 1;

        $upper = $this->upper === null || $this->includeUpper
            ? $this->upper
            : $this->upper - 1;

        return [$lower, $upper];
    }
}
