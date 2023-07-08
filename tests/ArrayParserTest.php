<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Pgsql\ArrayParser;

/**
 * @group pgsql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class ArrayParserTest extends TestCase
{
    public function testParser(): void
    {
        $this->assertSame([0 => null, 1 => null], (new ArrayParser('{NULL,NULL}'))->parse());
        $this->assertSame([], (new ArrayParser('{}'))->parse());
        $this->assertSame([0 => null, 1 => null], (new ArrayParser('{,}'))->parse());
        $this->assertSame([0 => '1', 1 => '2', 2 => '3'], (new ArrayParser('{1,2,3}'))->parse());
        $this->assertSame([0 => '1', 1 => '-2', 2 => null, 3 => '42'], (new ArrayParser('{1,-2,NULL,42}'))->parse());
        $this->assertSame([[0 => 'text'], [0 => null], [0 => '1']], (new ArrayParser('{{text},{NULL},{1}}'))->parse());
        $this->assertSame(
            [[0 => '[",","null",true,"false","f"]'], 1 => ''],
            (new ArrayParser('{{"[\",\",\"null\",true,\"false\",\"f\"]"},""}'))->parse()
        );
    }
}
