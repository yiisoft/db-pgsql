<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Expression;

use DateTimeImmutable;
use Yiisoft\Db\Expression\ExpressionInterface;

final class DateRangeValue implements ExpressionInterface
{
    public function __construct(
        public readonly ?DateTimeImmutable $lower,
        public readonly ?DateTimeImmutable $upper,
        public readonly bool $includeLower,
        public readonly bool $includeUpper,
    ) {}
}
