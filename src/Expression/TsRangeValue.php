<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Expression;

use DateTimeImmutable;

/**
 * @implements RangeValueInterface<DateTimeImmutable>
 */
final class TsRangeValue implements RangeValueInterface
{
    public function __construct(
        public readonly ?DateTimeImmutable $lower = null,
        public readonly ?DateTimeImmutable $upper = null,
        public readonly bool $includeLower = true,
        public readonly bool $includeUpper = true,
    ) {}

    public function getBounds(): array
    {
        $lower = $this->lower === null || $this->includeLower
            ? $this->lower
            : $this->lower->modify('+1 second');

        $upper = $this->upper === null || $this->includeUpper
            ? $this->upper
            : $this->upper->modify('-1 second');

        return [$lower, $upper];
    }
}
