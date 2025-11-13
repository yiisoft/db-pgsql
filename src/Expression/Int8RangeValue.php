<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Expression;

use Yiisoft\Db\Expression\ExpressionInterface;

final class Int8RangeValue implements ExpressionInterface
{
    public function __construct(
        public readonly int|string|null $lower,
        public readonly int|string|null $upper,
        public readonly bool $includeLower,
        public readonly bool $includeUpper,
    ) {}
}
