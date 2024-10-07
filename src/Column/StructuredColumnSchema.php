<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Column;

use Yiisoft\Db\Pgsql\StructuredParser;
use Yiisoft\Db\Schema\Column\StructuredColumnSchema as BaseStructuredColumnSchema;
use Yiisoft\Db\Syntax\ParserToArrayInterface;

final class StructuredColumnSchema extends BaseStructuredColumnSchema
{
    protected function getParser(): ParserToArrayInterface
    {
        return new StructuredParser();
    }
}
