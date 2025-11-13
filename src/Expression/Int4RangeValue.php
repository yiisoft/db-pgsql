<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Expression;

use Yiisoft\Db\Expression\ExpressionInterface;

final class Int4RangeValue implements ExpressionInterface
{
    public function __construct(
        public readonly ?int $lower,
        public readonly ?int $upper,
        public readonly bool $includeLower,
        public readonly bool $includeUpper,
    ) {}
}
