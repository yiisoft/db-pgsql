<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Column;

use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Schema\SchemaInterface;

final class BigIntColumnSchema extends AbstractIntegerColumnSchema
{
    public function __construct(string $name)
    {
        parent::__construct($name);

        $this->type(SchemaInterface::TYPE_BIGINT);
    }

    public function dbTypecast(mixed $value): int|string|ExpressionInterface|null
    {
        return match (true) {
            is_int($value), $value === null, $value instanceof ExpressionInterface => $value,
            $value === '' => null,
            $value === false => 0,
            PHP_INT_MIN <= $value && $value <= PHP_INT_MAX => (int) $value,
            default => (string) $value,
        };
    }

    public function phpTypecast(mixed $value): int|string|null
    {
        /** @psalm-var int|string|null $value */
        return match (true) {
            $value === null => null,
            PHP_INT_MIN <= $value && $value <= PHP_INT_MAX => (int) $value,
            default => (string) $value,
        };
    }
}
