<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Pgsql\Composite\CompositeParser;

/**
 * @group pgsql
 */
final class CompositeParserTest extends TestCase
{
    public function testParser(): void
    {
        $compositeParse = new CompositeParser();

        $this->assertSame([null], $compositeParse->parse('()'));
        $this->assertSame([0 => null, 1 => null], $compositeParse->parse('(,)'));
        $this->assertSame([0 => '1', 1 => '2', 2 => '3'], $compositeParse->parse('(1,2,3)'));
        $this->assertSame([0 => '1', 1 => '-2', 2 => null, 3 => '42'], $compositeParse->parse('(1,-2,,42)'));
        $this->assertSame([0 => ''], $compositeParse->parse('("")'));
        $this->assertSame(
            [0 => '[",","null",true,"false","f"]'],
            $compositeParse->parse('("[\",\",\"null\",true,\"false\",\"f\"]")')
        );

        // Default values can have any expressions
        $this->assertSame(null, $compositeParse->parse("'(1,2,3)::composite_type'"));
    }
}
