<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Column;

use Yiisoft\Db\Pgsql\StructuredParser;
use Yiisoft\Db\Schema\Column\StructuredColumn as BaseStructuredColumn;
use Yiisoft\Db\Syntax\ParserToArrayInterface;

final class StructuredColumn extends BaseStructuredColumn
{
    protected function getParser(): ParserToArrayInterface
    {
        return new StructuredParser();
    }
}
