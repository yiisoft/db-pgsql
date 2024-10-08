<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Column;

use Yiisoft\Db\Pgsql\ArrayParser;
use Yiisoft\Db\Schema\Column\ArrayColumnSchema as BaseArrayColumnSchema;
use Yiisoft\Db\Syntax\ParserToArrayInterface;

final class ArrayColumnSchema extends BaseArrayColumnSchema
{
    protected function getParser(): ParserToArrayInterface
    {
        return new ArrayParser();
    }
}
