<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Expression;

use Yiisoft\Db\Expression\ExpressionInterface;

final class NumRangeValue implements ExpressionInterface
{
    public function __construct(
        public readonly ?float $lower = null,
        public readonly ?float $upper = null,
        public readonly bool $includeLower = true,
        public readonly bool $includeUpper = true,
    ) {}
}
