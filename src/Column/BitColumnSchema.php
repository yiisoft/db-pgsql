<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Column;

use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Pgsql\Schema;
use Yiisoft\Db\Schema\Column\AbstractColumnSchema;
use Yiisoft\Db\Schema\SchemaInterface;

use function bindec;
use function decbin;
use function gettype;
use function str_pad;

final class BitColumnSchema extends AbstractColumnSchema
{
    public function __construct(
        string $type = Schema::TYPE_BIT,
        string|null $phpType = SchemaInterface::PHP_TYPE_INTEGER,
    ) {
        parent::__construct($type, $phpType);
    }

    /** @psalm-suppress RedundantCast */
    public function dbTypecast(mixed $value): string|ExpressionInterface|null
    {
        return match (gettype($value)) {
            'integer', 'double' => str_pad(decbin((int) $value), (int) $this->getSize(), '0', STR_PAD_LEFT),
            'NULL' => null,
            'boolean' => $value ? '1' : '0',
            'string' => $value === '' ? null : $value,
            default => $value instanceof ExpressionInterface ? $value : (string) $value,
        };
    }

    public function phpTypecast(mixed $value): int|null
    {
        /** @var int|string|null $value */
        if (is_string($value)) {
            /** @var int */
            return bindec($value);
        }

        return $value;
    }
}
