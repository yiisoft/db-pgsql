<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Column;

use Yiisoft\Db\Schema\Column\BooleanColumnSchema as BaseBooleanColumnSchema;

final class BooleanColumnSchema extends BaseBooleanColumnSchema
{
    public function phpTypecast(mixed $value): bool|null
    {
        if ($value === null) {
            return null;
        }

        return $value && $value !== 'f';
    }
}
