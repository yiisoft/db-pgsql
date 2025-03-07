<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Data;

use Yiisoft\Db\Schema\Data\AbstractStructuredLazyArray;

final class StructuredLazyArray extends AbstractStructuredLazyArray
{
    protected function parse(string $value): array|null
    {
        return (new StructuredParser())->parse($value);
    }
}
