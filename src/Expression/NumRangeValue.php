<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Expression;

use Yiisoft\Db\Expression\ExpressionInterface;

final class NumRangeValue implements ExpressionInterface
{
    public function __construct(
        public readonly ?float $lower,
        public readonly ?float $upper,
        public readonly bool $includeLower,
        public readonly bool $includeUpper,
    ) {}
}
