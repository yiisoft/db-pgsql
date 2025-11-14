<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Expression;

use DateTimeImmutable;
use Yiisoft\Db\Expression\ExpressionInterface;

final class DateRangeValue implements ExpressionInterface
{
    public function __construct(
        public readonly ?DateTimeImmutable $lower = null,
        public readonly ?DateTimeImmutable $upper = null,
        public readonly bool $includeLower = true,
        public readonly bool $includeUpper = true,
    ) {}
}
