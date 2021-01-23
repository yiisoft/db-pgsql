<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use Yiisoft\Db\Pgsql\ArrayParser;

/**
 * @group pgsql
 */
final class ArrayParserTest extends TestCase
{
    public function testParser(): void
    {
        $arrayParse = new ArrayParser();

        $this->assertNull($arrayParse->parse(null));
        $this->assertSame([0 => null, 1 => null], $arrayParse->parse('{NULL,NULL}'));
        $this->assertSame([], $arrayParse->parse('{}'));
        $this->assertSame([0 => null, 1 => null], $arrayParse->parse('{,}'));
        $this->assertSame([0 => '1', 1 => '2', 2 => '3'], $arrayParse->parse('{1,2,3}'));
        $this->assertSame([0 => '1', 1 => '-2', 2 => null, 3 => '42'], $arrayParse->parse('{1,-2,NULL,42}'));
        $this->assertSame([[0 => 'text'], [0 => null], [0 => '1']], $arrayParse->parse('{{text},{NULL},{1}}'));
        $this->assertSame(
            [[0 => '[",","null",true,"false","f"]'], 1 => ''],
            $arrayParse->parse('"{"[\",\",\"null\",true,\"false\",\"f\"]"}"')
        );
    }
}
