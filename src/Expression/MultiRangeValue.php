<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Expression;

use Yiisoft\Db\Expression\ExpressionInterface;

final class MultiRangeValue implements ExpressionInterface
{
    /**
     * @var array $ranges
     * @psalm-var array<string|ExpressionInterface> $ranges
     */
    public readonly array $ranges;

    public function __construct(
        string|ExpressionInterface ...$ranges
    ) {
        $this->ranges = $ranges;
    }
}
