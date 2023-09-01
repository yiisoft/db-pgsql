<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Column;

use Yiisoft\Db\Expression\ExpressionInterface;

final class IntegerColumnSchema extends AbstractIntegerColumnSchema
{
    public function dbTypecast(mixed $value): int|ExpressionInterface|null
    {
        return match (true) {
            is_int($value), $value === null, $value instanceof ExpressionInterface => $value,
            $value === '' => null,
            default => (int) $value,
        };
    }

    public function phpTypecast(mixed $value): int|null
    {
        if ($value === null) {
            return null;
        }

        return (int) $value;
    }
}
