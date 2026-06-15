<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Expression;

use Yiisoft\Db\Expression\ExpressionInterface;

/**
 * @template T
 */
interface RangeValueInterface extends ExpressionInterface
{
    /**
     * Returns the lower and upper bounds of a range, inclusive.
     *
     * @psalm-return array{0: ?T, 1: ?T}
     */
    public function getBounds(): array;
}
