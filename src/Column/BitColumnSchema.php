<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Column;

use Yiisoft\Db\Pgsql\Schema;
use Yiisoft\Db\Schema\Column\AbstractColumnSchema;
use Yiisoft\Db\Schema\SchemaInterface;

use function bindec;
use function decbin;
use function is_int;
use function str_pad;

final class BitColumnSchema extends AbstractColumnSchema
{
    public function __construct(string $name)
    {
        parent::__construct($name);

        $this->type(Schema::TYPE_BIT);
        $this->phpType(SchemaInterface::PHP_TYPE_INTEGER);
    }

    public function dbTypecast(mixed $value): mixed
    {
        return match (true) {
            is_int($value), is_float($value) => str_pad(decbin((int) $value), (int) $this->getSize(), '0', STR_PAD_LEFT),
            $value === null, $value === '' => null,
            $value => '1',
            $value === false => '0',
            default => $value,
        };
    }

    public function phpTypecast(mixed $value): int|null
    {
        /** @psalm-var int|string|null $value */
        if (is_string($value)) {
            /** @psalm-var int */
            return bindec($value);
        }

        return $value;
    }
}
