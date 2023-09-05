<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Column;

use Yiisoft\Db\Schema\Column\BinaryColumnSchema as BaseBinaryColumnSchema;

use function hex2bin;
use function is_string;
use function str_starts_with;
use function substr;

final class BinaryColumnSchema extends BaseBinaryColumnSchema
{
    public function phpTypecast(mixed $value): mixed
    {
        if (is_string($value) && str_starts_with($value, '\x')) {
            return hex2bin(substr($value, 2));
        }

        return $value;
    }
}
